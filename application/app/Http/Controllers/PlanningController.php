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

    // 1️⃣ Identification du Top Manager (Melissa)
    $user = Auth::user();
    $email = $user->work_email ?? $user->email;
    $topManager = Agent::with('projets')->where('work_email', $email)->firstOrFail();
    $projetIds = $topManager->projets->pluck('id')->toArray();

    // 2️⃣ Récupérer l'ID du rôle "Manager"
    $managerRoleId = Role::where('name', 'Manager')->value('id');

    // 3️⃣ Récupération des agents avec rôle Manager et dans les projets du Top Manager
    $agents = DB::table('agents as a')
        ->join('agent_projet as ap', 'a.id', '=', 'ap.agent_id')
        ->join('projets as p', 'ap.projet_id', '=', 'p.id')
        ->join('model_has_roles as mhr', 'a.id', '=', 'mhr.model_id') // pivot Laravel Permission
        ->leftJoin('agents as mgr', 'a.manager', '=', 'mgr.workday_id')
        ->whereIn('ap.projet_id', $projetIds)
        ->where('mhr.role_id', $managerRoleId) // <-- filtre rôle Manager
        ->select(
            'a.id', 
            'a.prenom', 
            'a.nom', 
            'a.fonction',
            'p.designation as nom_projet',
            DB::raw("COALESCE(CONCAT(mgr.prenom, ' ', mgr.nom), 'Direction') as nom_manager")
        )
        ->distinct()
        ->get()
        ->sortBy('nom_projet');

    // 4️⃣ Récupération des fonctions disponibles (pour le filtre)
    $categoriesDispo = $agents->pluck('fonction')->unique()->sort()->toArray();

    // 5️⃣ Gestion du filtre (Si rien n'est coché, on affiche tout par défaut)
    $fonctionsChoisies = $request->input('fonctions', $categoriesDispo);

    // 6️⃣ Filtrage des agents selon les fonctions choisies
    $agents = $agents->filter(fn($a) => in_array($a->fonction, (array)$fonctionsChoisies));

    // 7️⃣ Chargement des plannings
    $agentIds = $agents->pluck('id')->unique();
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
    /**
     * Affiche la page de la Planification Globale (Hebdomadaire)
     */
    public function PlanningGlobal(Request $request): View
{
    $selectedWeek = $request->input('week', now()->format('Y-W')); 
    $user = Auth::user();
    
    // 1. Définition des super-privilèges
    $isAdmin = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

    if ($isAdmin) {
        // L'Admin voit tout
        $sites = Projet::distinct()->whereNotNull('site_id')->pluck('site_id');
        $projetsQuery = Projet::query();
    } else {
        // 2. 🔐 Sécurité Top Manager : On récupère l'agent lié via work_email
        $agentConnecte = Agent::with('projets')->where('work_email', $user->work_email)->first();
        
        if (!$agentConnecte) {
            abort(403, "Profil agent introuvable pour restreindre l'accès.");
        }

        // Liste des sites auxquels appartiennent ses projets
        $sites = $agentConnecte->projets->pluck('site_id')->unique();
        
        // 🚨 Restriction stricte : Il ne peut requêter que ses propres projets
        $projetsQuery = Projet::whereIn('id', $agentConnecte->projets->pluck('id'));
    }

    $selectedSiteId = $request->input('site_id');
    $selectedProjetId = $request->input('projet_id');

    // 3. Application des filtres de recherche
    $projetsList = $projetsQuery->when($selectedSiteId, function($q) use ($selectedSiteId) {
        return $q->where('site_id', $selectedSiteId);
    })->get();

    // 4. Validation de sécurité supplémentaire (Anti-ID Guessing)
    // Si un Top Manager essaie de forcer un projet_id dans l'URL qui n'est pas dans sa liste
    if (!$isAdmin && $selectedProjetId) {
        if (!$projetsList->contains('id', $selectedProjetId)) {
            $selectedProjetId = null; // On reset le filtre s'il n'appartient pas au manager
        }
    }

    // 5. Génération du sélecteur de semaines (inchangé)
    $semaines = collect();
    $currentWeekNum = now()->weekOfYear;
    for ($i = $currentWeekNum - 3; $i <= $currentWeekNum + 3; $i++) {
        if ($i < 1 || $i > 53) continue;
        $start = Carbon::now()->setISODate(now()->year, $i)->startOfWeek();
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

    /**
     * API : Données pour la vue HEBDOMADAIRE (Route: /planning/api/data)
     */
    

        public function getPlanningData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            // Correction ici : Un Top Manager n'a pas forcément FullAccess, il est restreint à ses projets
            $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

            $rawWeek = $request->input('week', now()->format('Y-W')); 
            $parts = explode('-', str_replace('-W', '-', $rawWeek));
            $year = (int)$parts[0];
            $weekNum = (int)($parts[1] ?? now()->weekOfYear); 

            $formatSimple = $year . '-' . $weekNum;
            $formatZero   = $year . '-' . str_pad($weekNum, 2, '0', STR_PAD_LEFT); 

            $dateDebut = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
            $dates = [];
            for ($i = 0; $i < 7; $i++) { $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d'); }

            // 1. Récupération des projets autorisés (Eager Loading des agents pour éviter le N+1)
            $queryProjets = Projet::with(['agents' => function($q) {
                $q->whereHas('user', fn($uq) => $uq->role('Manager'));
            }]);

            if ($isFullAccess) {
                if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
                if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') $queryProjets->where('id', $request->get('projet_id'));
            } else {
                // Le Top Manager ne voit que les projets où il est lui-même affecté
                $queryProjets->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
            }
            $projets = $queryProjets->get();

            // 2. Pré-chargement massif des données pour la performance
            $allAgentIds = $projets->pluck('agents.*.id')->flatten()->unique();
            $allPlannings = Planning::whereIn('agent_id', $allAgentIds)
                ->whereIn('semaine', [$formatSimple, $formatZero])
                ->get()
                ->groupBy('agent_id');

            // Pré-récupération de tous les "Boss" (Top Managers) en une seule requête
            $managerIds = $projets->pluck('agents.*.manager')->flatten()->filter()->unique();
            $allBosses = Agent::whereIn('workday_id', $managerIds)->get()->keyBy('workday_id');

            $resultat = [];
            foreach ($projets as $projet) {
                $groupes = [];
                foreach ($projet->agents as $agent) {
                    // On récupère le boss depuis notre collection en mémoire (Vitesse ++ )
                    $boss = $allBosses->get($agent->manager);
                    $bossName = $boss ? "{$boss->prenom} {$boss->nom}" : "Direction / Autre";
                    
                    if (!isset($groupes[$bossName])) {
                        $groupes[$bossName] = ['manager' => $bossName, 'agents' => []];
                    }

                    $statsPlanning = [];
                    $agentPlannings = $allPlannings->get($agent->id) ?? collect();
                    
                    foreach ($dates as $date) {
                        // On compare les dates proprement
                        $p = $agentPlannings->first(fn($v) => Carbon::parse($v->jour)->format('Y-m-d') === $date);
                        $statsPlanning[$date] = [
                            'in'  => ($p && $p->entree) ? Carbon::parse($p->entree)->format('H:i') : null,
                            'out' => ($p && $p->sortie) ? Carbon::parse($p->sortie)->format('H:i') : null,
                        ];
                    }

                    $groupes[$bossName]['agents'][] = [
                        'nom' => $agent->nom, 
                        'prenom' => $agent->prenom, 
                        'fonction' => $agent->fonction ?? 'MANAGER', 
                        'planning' => $statsPlanning
                    ];
                }
                if (!empty($groupes)) {
                    $resultat[] = [
                        'site' => $projet->site_id, 
                        'projet' => $projet->designation, 
                        'groupes' => array_values($groupes)
                    ];
                }
            }
            return response()->json(['dates' => $dates, 'resultat' => $resultat]);

        } catch (\Exception $e) {
            Log::error("API Hebdo Error: " . $e->getMessage());
            return response()->json(['error' => "Erreur lors du chargement des données."], 500);
        }
    }

    /**
     * Affiche la page du Graphique Journalier
     */
    
        public function dailyView(Request $request): View
    {
        $user = auth()->user();
        $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        // On pré-charge les relations pour éviter le N+1
        $query = Projet::query();

        if (!$isFullAccess) {
            $query->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
        }

        $projetsList = $query->get();
        $sites = $projetsList->pluck('site_id')->unique()->filter();

        return view('planning.daily', [
            'sites'            => $sites,
            'projetsList'      => $projetsList,
            'selectedSiteId'   => $request->input('site_id'),
            'selectedProjetId' => $request->input('projet_id'),
            'filtreFixe'       => !$isFullAccess 
        ]);
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
            'week' => 'required|integer' // On reçoit le numéro (ex: 9)
        ]);

        $authUserId = Auth::id();
        $today = now()->startOfDay();
        $selectedYear = 2026; 
        // On recrée le format YYYY-WW pour la base de données
        $weekFormat = $selectedYear . '-' . str_pad($request->input('week'), 2, '0', STR_PAD_LEFT);

        // 1. Vérification des droits de Melissa (Top Manager)
        $topManager = Agent::with('projets')->where('work_email', Auth::user()->work_email)->firstOrFail();
        $allowedProjetIds = $topManager->projets->pluck('id')->toArray();

        // 2. Pré-chargement de tous les agents concernés pour éviter les requêtes en boucle (N+1)
        $submittedAgentIds = array_keys($request->input('plannings'));
        $agentsCibles = Agent::with('projets')->whereIn('id', $submittedAgentIds)->get()->keyBy('id');

        try {
            DB::beginTransaction();

            foreach ($request->input('plannings') as $agentId => $jours) {
                $agent = $agentsCibles->get($agentId);

                // Vérification de sécurité : l'agent appartient-il aux projets de Melissa ?
                if (!$agent || empty(array_intersect($allowedProjetIds, $agent->projets->pluck('id')->toArray()))) {
                    continue;
                }

                foreach ($jours as $date => $heures) {
                    // Interdiction de modifier le passé
                    if (Carbon::parse($date)->startOfDay()->isBefore($today)) {
                        continue;
                    }

                    // Si les deux champs sont vides, on peut choisir de supprimer ou d'ignorer
                    if (empty($heures['entree']) && empty($heures['sortie'])) {
                        // Optionnel : Supprimer l'entrée si Melissa a effacé les heures
                        // Planning::where('agent_id', $agentId)->where('jour', $date)->delete();
                        continue;
                    }

                    // Enregistrement avec le format de semaine correct
                    Planning::updateOrCreate(
                        [
                            'agent_id' => $agentId, 
                            'jour'     => $date
                        ],
                        [
                            'entree'   => $heures['entree'],
                            'sortie'   => $heures['sortie'],
                            'semaine'  => $weekFormat, // Format 2026-09
                            'user_id'  => $authUserId
                        ]
                    );
                }
            }

            DB::commit();
            return back()->with('success', "Planning de la semaine {$request->input('week')} enregistré avec succès.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'enregistrement manuel : " . $e->getMessage());
            return back()->with('error', "Une erreur technique est survenue. Vérifiez les logs.");
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

    public function getDailyPlanningData(Request $request): JsonResponse
{
    try {
        $user = auth()->user();
        $dateRaw = $request->get('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($dateRaw)->format('Y-m-d');

        $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        // 1. Correction de la requête : On pointe sur la colonne 'jour' définie dans ton modèle Planning
        $queryProjets = Projet::with(['agents' => function($q) use ($date) {
            $q->with(['plannings' => function($pq) use ($date) {
                $pq->whereDate('jour', $date); // 'jour' est le nom exact dans ta table
            }]);
            // On garde les pointages pour le calcul du travail réel
            $q->with(['pointages' => function($ptq) use ($date) {
                $ptq->whereDate('date_pointage', $date);
            }]);
        }]);

        // Filtres de sécurité
        if (!$isFullAccess) {
            $queryProjets->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
        }
        if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
        if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') {
            $queryProjets->where('id', $request->get('projet_id'));
        }

        $projets = $queryProjets->get();
        $managerIds = $projets->pluck('agents.*.manager')->flatten()->filter()->unique();
        $allBosses = Agent::whereIn('workday_id', $managerIds)->get()->keyBy('workday_id');

        $resultat = [];

        foreach ($projets as $projet) {
            $topManagersGroups = [];

            foreach ($projet->agents as $agent) {
                // IMPORTANT : On récupère le planning du jour
                $planning = $agent->plannings->first();

                // FILTRE : Si pas de planning ce jour-là, on n'affiche pas (comme demandé)
                if (!$planning) continue;

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

                $analysis = $hasPointage ? $this->analyzePointage($pointage, $date) : null;

                // On injecte les données de planning dans le tableau
                $topManagersGroups[$topManagerName]['managers'][] = [
                    'nom' => "{$agent->prenom} {$agent->nom}",
                    'role' => $agent->fonction ?? 'Manager',
                    
                    // DONNÉES DE PLANNING (Essentiel pour le JS)
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
    // 1. Validation de base
    $request->validate([
        'pasted_data' => 'required',
        'week' => 'required|integer' 
    ]);

    $user = auth()->user();
    $data = $request->input('pasted_data');
    $today = now()->startOfDay();

    // 2. 🔐 Vérification des Rôles (Identique à l'import Excel)
    $isAdmin = $user->hasRole('Admin IT');
    $isTopManager = $user->hasRole('Top Manager');

    if (!$isAdmin && !$isTopManager) {
        return back()->with('error', "Accès refusé : Votre rôle ne permet pas l'importation.");
    }

    // 3. 🌍 Portée (Scope) pour le Top Manager
    $allowedProjetIds = [];
    if ($isTopManager && !$isAdmin) {
        $currentAgent = Agent::with('projets')
            ->where('work_email', $user->work_email) 
            ->first();

        if (!$currentAgent) {
            return back()->with('error', "Profil agent introuvable pour l'email : {$user->work_email}");
        }
        $allowedProjetIds = $currentAgent->projets->pluck('id')->toArray();
    }

    $lines = explode("\n", str_replace("\r", "", trim($data)));
    $successCount = 0;
    $errors = [];

    foreach ($lines as $index => $line) {
        $columns = explode("\t", $line);

        // Ignorer l'en-tête
        if ($index === 0 && (str_contains(strtolower($columns[0]), 'id') || str_contains(strtolower($columns[1]), 'date'))) {
            continue;
        }

        if (count($columns) >= 2) {
            try {
                $workdayIdExcel = trim($columns[0]); 
                $rawDate = trim($columns[1]);
                
                // 4. Recherche de l'agent cible et vérification des droits
                $targetAgent = Agent::with(['projets', 'user.roles'])
                    ->where('workday_id', $workdayIdExcel)
                    ->first();

                // Règle : Agent existant et rôle Manager
                if (!$targetAgent || !$targetAgent->user || !$targetAgent->user->hasRole('Manager')) {
                    throw new \Exception("Agent non trouvé ou n'est pas un Manager (ID: $workdayIdExcel)");
                }

                // Règle : Restriction projet pour Top Manager
                if ($isTopManager && !$isAdmin) {
                    $targetProjetIds = $targetAgent->projets->pluck('id')->toArray();
                    if (empty(array_intersect($allowedProjetIds, $targetProjetIds))) {
                        throw new \Exception("Vous n'avez pas les droits sur le projet de cet agent (ID: $workdayIdExcel)");
                    }
                }

                // 5. Traitement de la Date
                try {
                    $dateObj = str_contains($rawDate, '/') 
                        ? \Carbon\Carbon::createFromFormat('d/m/Y', $rawDate)
                        : \Carbon\Carbon::parse($rawDate);
                } catch (\Exception $e) {
                    throw new \Exception("Date invalide : $rawDate");
                }

                // Sécurité : Pas de modifications dans le passé (comme dans ton Excel)
                if ($dateObj->startOfDay()->isBefore($today)) {
                    continue; 
                }

                // 6. Formatage Heures et Semaine
                $entree = !empty($columns[2]) && strtoupper(trim($columns[2])) !== 'OFF' ? trim($columns[2]) : null;
                $sortie = !empty($columns[3]) && strtoupper(trim($columns[3])) !== 'OFF' ? trim($columns[3]) : null;
                $weekISO = $dateObj->format('o-W');

                // 7. Sauvegarde
                \App\Models\Planning::updateOrCreate(
                    [
                        'agent_id' => $targetAgent->id,
                        'jour'     => $dateObj->format('Y-m-d')
                    ],
                    [
                        'entree'   => $entree,
                        'sortie'   => $sortie,
                        'semaine'  => $weekISO,
                        'user_id'  => $user->id
                    ]
                );

                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($index + 1) . " : " . $e->getMessage();
            }
        }
    }

    // 8. Feedback
    if (count($errors) > 0) {
        $errorPreview = implode(' | ', array_slice($errors, 0, 2));
        return back()->with('error', "$successCount importés. Erreurs : $errorPreview" . (count($errors) > 2 ? "..." : ""));
    }

    return back()->with('success', "Importation réussie : $successCount lignes de planning traitées.");
}

}


