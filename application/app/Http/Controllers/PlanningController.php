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
        $email = $user->work_email ?? $user->email;
        $topManager = Agent::with('projets')->where('work_email', $email)->firstOrFail();
        $projetIds = $topManager->projets->pluck('id')->toArray();

        $agents = DB::table('agents as a')

            ->join('users as u', 'a.work_email', '=', 'u.work_email')

            ->join('model_has_roles as mhr', function ($join) {
                $join->on('u.id', '=', 'mhr.model_id')
                    ->where('mhr.model_type', \App\Models\User::class);
            })

        ->join('roles as r', 'mhr.role_id', '=', 'r.id')

        ->join('agent_projet as ap', 'a.id', '=', 'ap.agent_id')
        ->join('projets as p', 'ap.projet_id', '=', 'p.id')

        ->leftJoin('agents as mgr', 'a.manager', '=', 'mgr.workday_id')

        ->whereIn('ap.projet_id', $projetIds)

        ->where('r.name', 'Manager')

        ->select(
            'a.id',
            'a.prenom',
            'a.nom',
            'a.fonction',
            'a.workday_id',
            'r.name as role_name',
            'p.designation as nom_projet',
            DB::raw("COALESCE(CONCAT(mgr.prenom, ' ', mgr.nom), 'Direction') as nom_manager")
        )

        ->distinct()
        ->orderBy('p.designation')
        ->get();

        $categoriesDispo = $agents->pluck('fonction')->unique()->sort()->values()->toArray();

        $fonctionsChoisies = (array) $request->input('fonctions', $categoriesDispo);

        $agents = $agents->filter(fn($a) => in_array($a->fonction, $fonctionsChoisies));

        //dd($agents);

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
 
        public function PlanningGlobal(Request $request): View
    {
        $selectedWeek = $request->input('week', now()->format('Y-W')); 
        $user = Auth::user();
        
        $isAdmin = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        if ($isAdmin) {
            $sites = Projet::distinct()->whereNotNull('site_id')->pluck('site_id');
            $projetsQuery = Projet::query();
        } else {
            $agentConnecte = Agent::with('projets')->where('work_email', $user->work_email)->first();
            
            if (!$agentConnecte) {
                abort(403, "Profil agent introuvable pour restreindre l'accès.");
            }

            $sites = $agentConnecte->projets->pluck('site_id')->unique();
            
            $projetsQuery = Projet::whereIn('id', $agentConnecte->projets->pluck('id'));
        }

        $selectedSiteId = $request->input('site_id');
        $selectedProjetId = $request->input('projet_id');

        $projetsList = $projetsQuery->when($selectedSiteId, function($q) use ($selectedSiteId) {
            return $q->where('site_id', $selectedSiteId);
        })->get();


        if (!$isAdmin && $selectedProjetId) {
            if (!$projetsList->contains('id', $selectedProjetId)) {
                $selectedProjetId = null; 
            }
        }

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


        public function getPlanningData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

            $rawWeek = $request->input('week', now()->format('Y-W')); 
            $parts = explode('-', str_replace('-W', '-', $rawWeek));
            $year = (int)$parts[0];
            $weekNum = (int)($parts[1] ?? now()->weekOfYear); 

            $dateDebut = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
            $dates = [];
            for ($i = 0; $i < 7; $i++) { $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d'); }

            // 1. Chargement des projets et agents
            $queryProjets = Projet::with(['agents' => function($q) use ($dates) {
                $q->whereHas('user', fn($uq) => $uq->role('Manager'))
                ->with(['plannings' => function($pq) use ($dates) {
                    $pq->whereIn('jour', $dates);
                }])
                ->with(['pointages' => function($ptq) use ($dates) {
                    $ptq->whereIn('date_pointage', $dates);
                }]);
            }]);

            if ($isFullAccess) {
                if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
                if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') $queryProjets->where('id', $request->get('projet_id'));
            } else {
                $queryProjets->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
            }

            $projets = $queryProjets->get();
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
                        
                        // Calcul simple de l'écart si pointage réel existe
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
                        'superviseurs' => $agentsFormatted // On utilise 'superviseurs' pour matcher le JS
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


