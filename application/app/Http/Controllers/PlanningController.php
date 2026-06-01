<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{Agent, Projet, Planning};
use App\Imports\PlanningsImport;
use Spatie\Permission\Models\Role;


class PlanningController extends Controller
{
    const TOLERANCE_RETARD = 5; 
    const TOLERANCE_PAUSE  = 10;
    const PAUSE_THEORIQUE  = 60;

    public function index(Request $request): View
{
    $selectedWeekNum = $request->input('week', now()->weekOfYear);
    $selectedYear = 2026; 
    $selectedWeekFull = $selectedYear . '-' . str_pad($selectedWeekNum, 2, '0', STR_PAD_LEFT);

    $user = Auth::user();
    
    // 1. Accès complet (Admin / RH / IT / Dir)
    $isFullAccess = ($user->work_email === 'admin@concentrix.com') || 
                    $user->hasAnyRole(['IT', 'RH', 'Directeur']);

    // 2. Identification du manager et de ses périmètres
    $email = $user->work_email ?? $user->email;
    $me = Agent::with('projets.site')->where('work_email', $email)->firstOrFail();
    
    $mySiteIds = $me->projets->pluck('site_id')->unique()->filter()->toArray();
    $myProjetIds = $me->projets->pluck('id')->toArray();

    // 3. Construction de la requête Query Builder
    $query = DB::table('agents as a')
        ->join('users as u', 'a.work_email', '=', 'u.work_email')
        ->join('model_has_roles as mhr', function ($join) {
            $join->on('u.id', '=', 'mhr.model_id')->where('mhr.model_type', \App\Models\User::class);
        })
        ->join('roles as r', 'mhr.role_id', '=', 'r.id')
        ->join('agent_projet as ap', 'a.id', '=', 'ap.agent_id')
        ->join('projets as p', 'ap.projet_id', '=', 'p.id')
        ->leftJoin('agents as mgr', 'a.manager', '=', 'mgr.workday_id');

    // 4. Logique de filtrage "Top" et périmètre
    if (!$isFullAccess) {
        if ($user->hasAnyRole(['Top Manager', 'Top Formateur', 'Top CQ', 'Top Superviseur'])) {
            
            // --- GESTION DU PÉRIMÈTRE (PROJET vs SITE) ---
            if ($user->hasRole('Top Superviseur')) {
                // Restriction spécifique au Top Superviseur : ses projets uniquement
                $query->whereIn('ap.projet_id', $myProjetIds);
            } else {
                // Les autres TOP voient tout le site géographique
                $query->whereIn('p.site_id', $mySiteIds);
            }

            // --- FILTRAGE PAR FONCTION ---
            if ($user->hasRole('Top Formateur')) {
                $query->where('a.fonction', 'LIKE', 'FORMATEUR%');
            } elseif ($user->hasRole('Top CQ')) {
                $query->where('a.fonction', 'LIKE', 'CONTRÔLEUR%');
            } elseif ($user->hasRole('Top Superviseur')) {
                $query->where('a.fonction', 'LIKE', 'SUPERVISEUR%');
            } elseif ($user->hasRole('Top Manager')) {
                // Top Manager : Accès aux 3 catégories sur son site
                $query->where(function($q) {
                    $q->where('a.fonction', 'LIKE', 'FORMATEUR%')
                      ->orWhere('a.fonction', 'LIKE', 'CONTRÔLEUR%')
                      ->orWhere('a.fonction', 'LIKE', 'SUPERVISEUR%');
                });
            }
        } else {
            // Manager de projet standard (Accès à ses projets uniquement)
            $query->whereIn('ap.projet_id', $myProjetIds);
        }
    }

    // Uniquement les profils avec le rôle "Manager" dans la table des rôles
    $query->where('r.name', 'Manager');

    $agents = $query->select(
            'a.id', 'a.prenom', 'a.nom', 'a.fonction', 'a.workday_id',
            'r.name as role_name', 'p.designation as nom_projet',
            DB::raw("COALESCE(CONCAT(mgr.prenom, ' ', mgr.nom), 'Direction') as nom_manager")
        )
        ->distinct()
        ->orderBy('p.designation')
        ->get();

    // 5. Filtrage final par catégories (UI) et Plannings
    $categoriesDispo = $agents->pluck('fonction')->unique()->sort()->values()->toArray();
    $fonctionsChoisies = (array) $request->input('fonctions', $categoriesDispo);
    
    // On filtre la collection d'agents selon les cases cochées dans la vue
    $agents = $agents->filter(fn($a) => in_array($a->fonction, $fonctionsChoisies));

    $agentIds = $agents->pluck('id')->unique();
    
    // Récupération des plannings pour la semaine sélectionnée
    $plannings = DB::table('plannings')
        ->where('semaine', $selectedWeekFull)
        ->whereIn('agent_id', $agentIds)
        ->get()
        ->map(function($item) {
            $item->entree = $item->entree ? \Carbon\Carbon::parse($item->entree)->format('H:i') : null;
            $item->sortie = $item->sortie ? \Carbon\Carbon::parse($item->sortie)->format('H:i') : null;
            return $item;
        })
        ->keyBy(fn($item) => $item->agent_id . '-' . $item->jour);

    return view('planning.index', [
        'agents' => $agents,
        'semaines' => $this->generateWeekRange($selectedYear, 0, 6),
        'selectedWeekNum' => $selectedWeekNum,
        'plannings' => $plannings,
        'categoriesDispo' => $categoriesDispo,
        'fonctionsChoisies' => $fonctionsChoisies
    ]);
}

    public function PlanningGlobal(Request $request): View
    {
        $selectedWeek = $request->input('week', now()->format('Y-W')); 
        $user = Auth::user();
        
        // 1. Accès complet (Directeur, IT, RH)
        $isAdmin = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        if ($isAdmin) {
            $sites = Projet::distinct()->whereNotNull('site_id')->pluck('site_id');
            $projetsQuery = Projet::query();
        } else {
            $agentConnecte = Agent::with('projets')->where('work_email', $user->work_email)->first();
            
            if (!$agentConnecte) {
                abort(403, "Profil agent introuvable.");
            }

            $mySiteIds = $agentConnecte->projets->pluck('site_id')->unique()->filter();
            $myProjetIds = $agentConnecte->projets->pluck('id');

            // --- LOGIQUE DE FILTRAGE PAR RÔLE ---
            
            // Cas A : Rôles "Transverses" par SITE (CQ, Formateur, Superviseur)
            if ($user->hasAnyRole(['Top Formateur', 'Top CQ', 'Top Superviseur'])) {
                $sites = $mySiteIds;
                $projetsQuery = Projet::whereIn('site_id', $mySiteIds);
            } 
            // Cas B : Top Manager & Managers standards (Uniquement leurs PROJETS rattachés)
            else {
                $sites = $mySiteIds;
                $projetsQuery = Projet::whereIn('id', $myProjetIds);
            }
        }

        $selectedSiteId = $request->input('site_id');
        $selectedProjetId = $request->input('projet_id');

        // Filtrage dynamique de la liste des projets pour le menu déroulant
        $projetsList = $projetsQuery->when($selectedSiteId, function($q) use ($selectedSiteId) {
            return $q->where('site_id', $selectedSiteId);
        })->orderBy('designation')->get();

        // Sécurité de sélection : on valide que le projet choisi est dans le périmètre autorisé
        if (!$isAdmin && $selectedProjetId) {
            if (!$projetsList->contains('id', $selectedProjetId)) {
                $selectedProjetId = null; 
            }
        }

        // Génération des semaines (Contexte 2026)
        $semaines = collect();
        $currentWeekNum = now()->weekOfYear;
        for ($i = $currentWeekNum - 3; $i <= $currentWeekNum + 3; $i++) {
            if ($i < 1 || $i > 53) continue;
            $start = Carbon::now()->setISODate(2026, $i)->startOfWeek(); 
            $semaines->push([
                'valeur' => $start->format('Y-W'), 
                'numero' => $i,
                'debut'  => $start->format('d/m'), 
                'fin'    => $start->endOfWeek()->format('d/m'),
            ]);
        }

        return view('planning.group', [
            'selectedWeek'     => $selectedWeek,
            'semaines'         => $semaines,
            'sites'            => $sites,
            'projetsList'      => $projetsList,
            'selectedSiteId'   => $selectedSiteId,
            'selectedProjetId' => $selectedProjetId,
            'filtreFixe'       => !$isAdmin
        ]);
    }

   public function getPlanningData(Request $request): JsonResponse
{
    try {
        $user = Auth::user();
        // 1. Accès complet (Directeur, IT, RH)
        $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        $rawWeek = $request->input('week', now()->format('Y-W')); 
        $parts = explode('-', str_replace('-W', '-', $rawWeek));
        $year = (int)$parts[0];
        $weekNum = (int)($parts[1] ?? now()->weekOfYear); 

        // Fixation de l'année à 2026 pour la cohérence projet
        $dateDebut = Carbon::now()->setISODate(2026, $weekNum)->startOfWeek();
        $dates = [];
        for ($i = 0; $i < 7; $i++) { 
            $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d'); 
        }

        // Identification de l'agent connecté pour les restrictions
        $me = Agent::with('projets')->where('work_email', $user->work_email)->first();
        $mySiteIds = $me ? $me->projets->pluck('site_id')->unique()->filter()->toArray() : [];
        $myProjetIds = $me ? $me->projets->pluck('id')->toArray() : [];

        // 2. Construction de la requête Projets
        $queryProjets = Projet::query();

        // Application des restrictions de périmètre sur les PROJETS
        if (!$isFullAccess) {
            if ($user->hasAnyRole(['Top Formateur', 'Top CQ'])) {
                // Vue sur tout le SITE géographique
                $queryProjets->whereIn('site_id', $mySiteIds);
            } 
            elseif ($user->hasRole('Top Superviseur')) {
                // RESTRICTION SPÉCIFIQUE : Uniquement ses projets
                $queryProjets->whereIn('id', $myProjetIds);
            }
            else {
                // Top Manager et Managers standards : Uniquement leurs PROJETS
                $queryProjets->whereIn('id', $myProjetIds);
            }
        }

        // Filtres optionnels venant de l'interface (Dropdowns)
        if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
        if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') $queryProjets->where('id', $request->get('projet_id'));

        // 3. Récupération avec filtrage des AGENTS (Fonctions)
        $projets = $queryProjets->with(['agents' => function($q) use ($dates, $user, $isFullAccess) {
            // Uniquement ceux qui ont le rôle Manager dans le système de permission
            $q->whereHas('user', fn($uq) => $uq->role('Manager'));

            // Restriction par fonction métier pour les rôles transverses
            if (!$isFullAccess) {
                if ($user->hasRole('Top Formateur')) {
                    $q->where('fonction', 'LIKE', 'FORMATEUR%');
                } elseif ($user->hasRole('Top CQ')) {
                    $q->where('fonction', 'LIKE', 'CONTRÔLEUR%');
                } elseif ($user->hasRole('Top Superviseur')) {
                    // Pour le Top Superviseur, on filtre aussi par sa fonction métier
                    $q->where('fonction', 'LIKE', 'SUPERVISEUR%');
                }
                // Note : Le Top Manager voit tous les agents de ses projets (pas de filtre fonction)
            }

            $q->with(['plannings' => fn($pq) => $pq->whereIn('jour', $dates)])
              ->with(['pointages' => fn($ptq) => $ptq->whereIn('date_pointage', $dates)]);
        }])->get();

        $resultat = [];
        foreach ($projets as $projet) {
            $agentsFormatted = [];
            foreach ($projet->agents as $agent) {
                $statsParDate = [];
                foreach ($dates as $date) {
                    $p = $agent->plannings->first(fn($v) => Carbon::parse($v->jour)->format('Y-m-d') === $date);
                    $pt = $agent->pointages->first(fn($v) => Carbon::parse($v->date_pointage)->format('Y-m-d') === $date);

                    $ecart = "00:00";
                    $status = 'normal';
                    
                    if ($p && $pt && $pt->entree && $pt->sortie) {
                        $theo = Carbon::parse($p->entree)->diffInMinutes(Carbon::parse($p->sortie));
                        $reel = Carbon::parse($pt->entree)->diffInMinutes(Carbon::parse($pt->sortie));
                        $diff = $reel - $theo;
                        $status = $diff < 0 ? 'deficit' : 'surplus';
                        $ecart = sprintf('%02d:%02d', abs(floor($diff / 60)), abs($diff % 60));
                    }

                    $statsParDate[$date] = [
                        'p_in'  => $p ? Carbon::parse($p->entree)->format('H:i') : null,
                        'p_out' => $p ? Carbon::parse($p->sortie)->format('H:i') : null,
                        'a_in'  => $pt ? Carbon::parse($pt->entree)->format('H:i') : null,
                        'a_out' => $pt ? Carbon::parse($pt->sortie)->format('H:i') : null,
                        'ecart' => ($ecart !== "00:00") ? $ecart : null,
                        'status'=> $status,
                        'retard'=> ($pt && $p && Carbon::parse($pt->entree)->gt(Carbon::parse($p->entree)->addMinutes(5))) ? 
                                    Carbon::parse($pt->entree)->diff(Carbon::parse($p->entree))->format('%H:%I') : null
                    ];
                }

                $agentsFormatted[] = [
                    'nom' => "{$agent->prenom} {$agent->nom}",
                    'fonction' => $agent->fonction,
                    'stats' => $statsParDate
                ];
            }

            if (!empty($agentsFormatted)) {
                $resultat[] = [
                    'projet' => $projet->designation,
                    'superviseurs' => $agentsFormatted 
                ];
            }
        }

        return response()->json(['dates' => $dates, 'resultat' => $resultat]);

    } catch (\Exception $e) {
        Log::error("API Hebdo Error: " . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function dailyView(Request $request): View
    {
        $user = auth()->user();
        $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        $queryProjets = Projet::query();

        if (!$isFullAccess) {
            $me = Agent::with('projets')->where('work_email', $user->work_email)->first();
            
            if (!$me) abort(403, "Profil agent introuvable.");

            $mySiteIds = $me->projets->pluck('site_id')->unique()->filter();
            $myProjetIds = $me->projets->pluck('id');

            // Filtrage du périmètre selon le rôle
            if ($user->hasAnyRole(['Top Formateur', 'Top CQ', 'Top Superviseur'])) {
                $queryProjets->whereIn('site_id', $mySiteIds);
            } else {
                // Top Manager et Managers standards
                $queryProjets->whereIn('id', $myProjetIds);
            }
        }

        $projetsList = $queryProjets->orderBy('designation')->get();
        $sites = $projetsList->pluck('site_id')->unique()->filter();

        return view('planning.daily', [
            'sites'            => $sites,
            'projetsList'      => $projetsList,
            'selectedSiteId'   => $request->input('site_id'),
            'selectedProjetId' => $request->input('projet_id'),
            'filtreFixe'       => !$isFullAccess 
        ]);
    }

   public function getDailyPlanningData(Request $request): JsonResponse
{
    try {
        $user = auth()->user();
        $dateRaw = $request->get('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($dateRaw)->format('Y-m-d');

        // 1. Accès complet (Admin / RH / IT / Dir)
        $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        // 2. Base de la requête avec filtrage des AGENTS par fonction
        $queryProjets = Projet::with(['agents' => function($q) use ($date, $user, $isFullAccess) {
            $q->whereHas('user', fn($uq) => $uq->role('Manager'));

            // --- FILTRAGE DES AGENTS PAR FONCTION ---
            if (!$isFullAccess) {
                if ($user->hasRole('Top Formateur')) {
                    $q->where('fonction', 'LIKE', 'FORMATEUR%');
                } elseif ($user->hasRole('Top CQ')) {
                    $q->where('fonction', 'LIKE', 'CONTRÔLEUR%');
                } elseif ($user->hasRole('Top Superviseur')) {
                    $q->where('fonction', 'LIKE', 'SUPERVISEUR%');
                }
                // Le Top Manager voit tous les agents de son projet (pas de filtre fonction)
            }

            $q->with(['plannings' => fn($pq) => $pq->whereDate('jour', $date)])
              ->with(['pointages' => fn($ptq) => $ptq->whereDate('date_pointage', $date)]);
        }]);

        // 3. FILTRAGE DES PROJETS (Périmètre géographique vs Projets rattachés)
        if (!$isFullAccess) {
            $me = Agent::with('projets')->where('work_email', $user->work_email)->first();
            $mySiteIds = $me ? $me->projets->pluck('site_id')->unique()->filter()->toArray() : [];
            $myProjetIds = $me ? $me->projets->pluck('id')->toArray() : [];

            if ($user->hasAnyRole(['Top Formateur', 'Top CQ'])) {
                // Ces rôles transverses voient tout le SITE
                $queryProjets->whereIn('site_id', $mySiteIds);
            } 
            elseif ($user->hasRole('Top Superviseur')) {
                // RESTRICTION SPÉCIFIQUE : Uniquement ses propres projets
                $queryProjets->whereIn('id', $myProjetIds);
            }
            else {
                // Top Manager et Managers standards : Uniquement leurs PROJETS
                $queryProjets->whereIn('id', $myProjetIds);
            }
        }

        // Filtres dynamiques (Dropdowns de l'interface)
        if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
        if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') {
            $queryProjets->where('id', $request->get('projet_id'));
        }

        $projets = $queryProjets->get();
        
        // Récupération des noms des managers pour le groupement par "N+1"
        $managerIds = $projets->pluck('agents.*.manager')->flatten()->filter()->unique();
        $allBosses = Agent::whereIn('workday_id', $managerIds)->get()->keyBy('workday_id');

        $resultat = [];

        foreach ($projets as $projet) {
            $topManagersGroups = [];

            foreach ($projet->agents as $agent) {
                $planning = $agent->plannings->first();
                if (!$planning) continue; // On n'affiche que les agents planifiés ce jour-là

                $pointage = $agent->pointages->first();
                $hasPointage = ($pointage !== null);
                
                $boss = $allBosses->get($agent->manager);
                $topManagerName = $boss ? "{$boss->prenom} {$boss->nom}" : "Direction / Hors Groupe";

                if (!isset($topManagersGroups[$topManagerName])) {
                    $topManagersGroups[$topManagerName] = [
                        'top_manager' => $topManagerName, 
                        'managers' => []
                    ];
                }

                // Analyse des retards et statuts
                $analysis = $hasPointage ? $this->analyzePointage($pointage, $date) : null;

                $topManagersGroups[$topManagerName]['managers'][] = [
                    'nom' => "{$agent->prenom} {$agent->nom}",
                    'role' => $agent->fonction ?? 'Manager',
                    'debut_theorique' => Carbon::parse($planning->entree)->format('H:i'),
                    'fin_theorique'   => Carbon::parse($planning->sortie)->format('H:i'),
                    'real_start' => $hasPointage ? Carbon::parse($pointage->entree)->format('H:i') : '--:--',
                    'real_end' => ($hasPointage && $pointage->sortie) ? Carbon::parse($pointage->sortie)->format('H:i') : '--:--',
                    'segments' => $hasPointage ? $this->calculateWorkSegments($pointage->entree, $pointage->sortie, $pointage->pause_debut, $pointage->pause_fin) : [],
                    'pauses' => $analysis['pauses'] ?? [],
                    'start_status' => $analysis['start_status'] ?? 'ABSENT',
                    'retard_minutes' => $analysis['retard_minutes'] ?? 0,
                ];
            }

            if (!empty($topManagersGroups)) {
                $resultat[] = [
                    'id_projet' => $projet->id, 
                    'site' => $projet->site_id, 
                    'projet' => $projet->designation, 
                    'top_managers' => array_values($topManagersGroups)
                ];
            }
        }

        return response()->json($resultat);

    } catch (\Exception $e) {
        \Log::error("API Journalier Error: " . $e->getMessage());
        return response()->json(['error' => "Erreur serveur: " . $e->getMessage()], 500);
    }
}
    
    private function calculateWorkSegments($entree, $sortie, $p_debut, $p_fin): array
    {
        $segments = []; if (!$entree) return $segments;
        $startStr = Carbon::parse($entree)->format('H:i');
        if ($p_debut && $p_fin) {
            $segments[] = ['start' => $startStr, 'end' => Carbon::parse($p_debut)->format('H:i'), 'type' => 'work'];
            $endWork = $sortie ? Carbon::parse($sortie)->format('H:i') : now()->format('H:i');
            $segments[] = ['start' => Carbon::parse($p_fin)->format('H:i'), 'end' => $endWork, 'type' => 'work'];
        } elseif ($sortie) {
            $segments[] = ['start' => $startStr, 'end' => Carbon::parse($sortie)->format('H:i'), 'type' => 'work'];
        } else {
            $segments[] = ['start' => $startStr, 'end' => now()->format('H:i'), 'type' => 'work'];
        }
        return $segments;
    }

    private function generateWeekRange(int $year, int $minus, int $plus): array
    {
        $semaines = []; $start = Carbon::now()->subWeeks($minus);
        for ($i = 0; $i <= ($minus + $plus); $i++) {
            $date = $start->copy()->addWeeks($i);
            $semaines[] = [
                'num' => $date->weekOfYear,
                'label' => "Semaine {$date->weekOfYear} ({$date->startOfWeek()->format('d/m')} - {$date->endOfWeek()->format('d/m')})",
                'full' => $date->year . '-' . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT)
            ];
        }
        return $semaines;
    }

   
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'plannings' => 'required|array',
            'week' => 'required|integer|min:1|max:53',
        ]);

        $authUser = Auth::user();
        $authUserId = $authUser->id;
        $today = now()->startOfDay();
        $selectedYear = 2026;

        $weekFormat = $selectedYear . '-' . str_pad($request->week, 2, '0', STR_PAD_LEFT);

        $topManager = Agent::with('projets')
            ->where('work_email', $authUser->work_email)
            ->firstOrFail();

        $allowedProjetIds = $topManager->projets->pluck('id')->toArray();

        $submittedAgentIds = array_keys($request->plannings);

        $agentsCibles = Agent::with('projets')
            ->whereIn('id', $submittedAgentIds)
            ->get()
            ->keyBy('id');

        try {
            DB::beginTransaction();

            foreach ($request->plannings as $agentId => $jours) {
                $agent = $agentsCibles->get($agentId);

                if (!$agent) {
                    continue;
                }

                $canModify = false;
                $agentFonction = strtolower(trim($agent->fonction ?? ''));

                // 1. Top Manager : peut modifier les agents/managers de ses projets
                if (
                    $authUser->hasRole('Top Manager') &&
                    !empty(array_intersect(
                        $allowedProjetIds,
                        $agent->projets->pluck('id')->toArray()
                    ))
                ) {
                    $canModify = true;
                }

                // 2. Top Formateur : peut modifier les formateurs de son site
                if (
                    $authUser->hasRole('Top Formateur') &&
                    $agent->site_id === $topManager->site_id &&
                    str_starts_with($agentFonction, 'formateur')
                ) {
                    $canModify = true;
                }

                // 3. Top CQ : peut modifier les contrôleurs qualité de son site
                if (
                    $authUser->hasRole('Top CQ') &&
                    $agent->site_id === $topManager->site_id &&
                    (
                        str_starts_with($agentFonction, 'cq') ||
                        str_starts_with($agentFonction, 'controlleur qualité') ||
                        str_starts_with($agentFonction, 'controleur qualité') ||
                        str_starts_with($agentFonction, 'contrôleur qualité')
                    )
                ) {
                    $canModify = true;
                }

                if (!$canModify) {
                    continue;
                }

                foreach ($jours as $date => $heures) {
                    if (Carbon::parse($date)->startOfDay()->isBefore($today)) {
                        continue;
                    }

                    $entree = $heures['entree'] ?? null;
                    $sortie = $heures['sortie'] ?? null;

                    if (empty($entree) && empty($sortie)) {
                        continue;
                    }

                    Planning::updateOrCreate(
                        [
                            'agent_id' => $agentId,
                            'jour' => $date,
                        ],
                        [
                            'entree' => $entree,
                            'sortie' => $sortie,
                            'semaine' => $weekFormat,
                            'user_id' => $authUserId,
                        ]
                    );
                }
            }

            DB::commit();

            return back()->with(
                'success',
                "Planning de la semaine {$request->week} enregistré avec succès."
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erreur lors de l'enregistrement du planning manuel", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return back()->with(
                'error',
                "Une erreur technique est survenue. Vérifiez les logs."
            );
        }
    }

    public function import(Request $request)
    {
        // Validation rapide du fichier
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $import = new PlanningsImport($request->input('week'));
        
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', "Erreur technique lors de l'import : " . $e->getMessage());
        }

        // 1. Gestion des erreurs de droits ou de profil (errorMessage défini dans collection())
        if ($import->errorMessage) {
            return back()->with('error', $import->errorMessage);
        }

        // 2. Succès : au moins une ligne a été importée
        if ($import->successCount > 0) {
            return back()->with('success', 
                "Importation réussie : {$import->successCount} planning(s) de Manager(s) créé(s) ou mis à jour."
            );
        }

        // 3. Cas où le fichier est valide mais aucune ligne ne correspondait aux critères (ex: pas de rôle Manager)
        return back()->with('info', "Aucune donnée n'a été importée. Vérifiez que le fichier contient bien des Managers de vos projets.");
    }



    private function analyzePointage($pointage, $date)
    {
        $pauses = [];
        $start_status = 'ABSENT';
        $end_status = '---';
        $retard_minutes = 0;
        $is_oubli = false;

        // --- 1. Retard à l'arrivée ---
        $heure_prevue_debut = Carbon::parse($date . ' 08:00:00'); // heure théorique de début
        if ($pointage->entree) {
            $heure_entree = Carbon::parse($pointage->entree);
            if ($heure_entree->gt($heure_prevue_debut)) {
                $diff = $heure_entree->diffInMinutes($heure_prevue_debut);
                if ($diff > (defined('self::TOLERANCE_RETARD') ? self::TOLERANCE_RETARD : 5)) {
                    $start_status = 'RETARD';
                    $retard_minutes = $diff;
                } else {
                    $start_status = 'OK';
                }
            } else {
                $start_status = 'OK';
            }
        }

        // --- 2. Analyse des pauses ---
        if ($pointage->pause_debut && $pointage->pause_fin) {
            $p_start = Carbon::parse($pointage->pause_debut);
            $p_end = Carbon::parse($pointage->pause_fin);
            $duree = $p_start->diffInMinutes($p_end);

            $status_pause = null;
            if ($duree > ((defined('self::PAUSE_THEORIQUE') ? self::PAUSE_THEORIQUE : 60) + (defined('self::TOLERANCE_PAUSE') ? self::TOLERANCE_PAUSE : 10))) {
                $status_pause = 'DEPASSEMENT';
            }

            $pauses[] = [
                'start' => $p_start->format('H:i'),
                'end' => $p_end->format('H:i'),
                'minutes' => $duree,
                'status' => $status_pause
            ];
        }

        // --- 3. Départ anticipé / oubli ---
        $heure_prevue_fin = Carbon::parse($date . ' 18:00:00'); // heure théorique de fin

        if ($pointage->sortie) {
            $heure_sortie = Carbon::parse($pointage->sortie);
            if ($heure_sortie->lt($heure_prevue_fin)) {
                $end_status = 'DEPART_ANTICIPE';
            } else {
                $end_status = 'OK';
            }
        } else {
            // Si pas de sortie et qu’il est déjà 1h après la fin prévue, on considère un oubli
            if (now()->gt($heure_prevue_fin->copy()->addHour())) {
                $is_oubli = true;
            }
        }

        return [
            'pauses' => $pauses,
            'start_status' => $start_status,
            'end_status' => $end_status,
            'retard_minutes' => $retard_minutes,
            'is_oubli' => $is_oubli
        ];
    }

  public function pasteImport(Request $request)
{
    $request->validate([
        'pasted_data' => 'required',
        'week' => 'required|integer|min:1|max:53',
    ]);

    $user = auth()->user();
    $data = $request->input('pasted_data');
    $today = now()->startOfDay();

    $isAdmin = $user->hasRole('Admin IT');
    $isTopManager = $user->hasRole('Top Manager');
    $isTopFormateur = $user->hasRole('Top Formateur');
    $isTopCQ = $user->hasRole('Top CQ');

    if (!$isAdmin && !$isTopManager && !$isTopFormateur && !$isTopCQ) {
        return back()->with('error', "Accès refusé : votre rôle ne permet pas l'importation.");
    }

    $currentAgent = Agent::with('projets')
        ->where('work_email', $user->work_email)
        ->first();

    if (!$isAdmin && !$currentAgent) {
        return back()->with('error', "Profil agent introuvable pour l'email : {$user->work_email}");
    }

    $allowedProjetIds = $currentAgent
        ? $currentAgent->projets->pluck('id')->toArray()
        : [];

    $lines = explode("\n", str_replace("\r", "", trim($data)));
    $successCount = 0;
    $errors = [];

    foreach ($lines as $index => $line) {
        $columns = explode("\t", $line);

        if ($index === 0 && isset($columns[0], $columns[1]) && (
            str_contains(strtolower($columns[0]), 'id') ||
            str_contains(strtolower($columns[1]), 'date')
        )) {
            continue;
        }

        if (count($columns) < 2) {
            continue;
        }

        try {
            $workdayIdExcel = trim($columns[0]);
            $rawDate = trim($columns[1]);

            $targetAgent = Agent::with(['projets', 'user.roles'])
                ->where('workday_id', $workdayIdExcel)
                ->first();

            if (!$targetAgent || !$targetAgent->user) {
                throw new \Exception("Agent non trouvé ou sans utilisateur lié (ID: $workdayIdExcel)");
            }

            $targetFonction = strtolower(trim($targetAgent->fonction ?? ''));
            $canImport = false;

            // Admin IT : accès total
            if ($isAdmin) {
                $canImport = true;
            }

            // Top Manager : uniquement les Managers de ses projets
            if (
                $isTopManager &&
                $targetAgent->user->hasRole('Manager') &&
                !empty(array_intersect(
                    $allowedProjetIds,
                    $targetAgent->projets->pluck('id')->toArray()
                ))
            ) {
                $canImport = true;
            }

            // Top Formateur : uniquement les Formateurs de son site
            if (
                $isTopFormateur &&
                $targetAgent->site_id === $currentAgent->site_id &&
                (
                    $targetAgent->user->hasRole('Formateur') ||
                    str_starts_with($targetFonction, 'formateur')
                )
            ) {
                $canImport = true;
            }

            // Top CQ : uniquement les CQ / Contrôleurs qualité de son site
            if (
                $isTopCQ &&
                $targetAgent->site_id === $currentAgent->site_id &&
                (
                    $targetAgent->user->hasRole('CQ') ||
                    str_starts_with($targetFonction, 'cq') ||
                    str_starts_with($targetFonction, 'controlleur qualité') ||
                    str_starts_with($targetFonction, 'controleur qualité') ||
                    str_starts_with($targetFonction, 'contrôleur qualité')
                )
            ) {
                $canImport = true;
            }

            if (!$canImport) {
                throw new \Exception("Vous n'avez pas les droits sur cet agent (ID: $workdayIdExcel)");
            }

            try {
                $dateObj = str_contains($rawDate, '/')
                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $rawDate)
                    : \Carbon\Carbon::parse($rawDate);
            } catch (\Exception $e) {
                throw new \Exception("Date invalide : $rawDate");
            }

            if ($dateObj->copy()->startOfDay()->isBefore($today)) {
                continue;
            }

            $entree = isset($columns[2]) && strtoupper(trim($columns[2])) !== 'OFF'
                ? trim($columns[2])
                : null;

            $sortie = isset($columns[3]) && strtoupper(trim($columns[3])) !== 'OFF'
                ? trim($columns[3])
                : null;

            $weekISO = $dateObj->format('o-W');

            Planning::updateOrCreate(
                [
                    'agent_id' => $targetAgent->id,
                    'jour' => $dateObj->format('Y-m-d'),
                ],
                [
                    'entree' => $entree,
                    'sortie' => $sortie,
                    'semaine' => $weekISO,
                    'user_id' => $user->id,
                ]
            );

            $successCount++;

        } catch (\Exception $e) {
            $errors[] = "Ligne " . ($index + 1) . " : " . $e->getMessage();
        }
    }

    if (count($errors) > 0) {
        $errorPreview = implode(' | ', array_slice($errors, 0, 2));

        return back()->with(
            'error',
            "$successCount importés. Erreurs : $errorPreview" . (count($errors) > 2 ? "..." : "")
        );
    }

    return back()->with(
        'success',
        "Importation réussie : $successCount lignes de planning traitées."
    );
}

}


