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

class PlanningController extends Controller
{

    const TOLERANCE_RETARD = 5; 
    const TOLERANCE_PAUSE  = 10;
    const PAUSE_THEORIQUE  = 60;
    /**
     * Vue de saisie hebdomadaire (Tableau avec Inputs)
     */
    public function index(Request $request): View
    {
        $categoriesSelectionnees = $request->input('fonctions', ['Superviseur', 'Formateur', 'CQ']);

        $fonctionMapping = [
            'Superviseur' => ['Superviseur', 'Team Leader Trainee, Operations', 'Team Leader, Operations', 'SUPERVISEUR'],
            'Formateur'   => ['Formateur', 'Trainer', 'FORMATEUR METIER', 'Trainer I - Trainee', 'Trainer II'],
            'CQ'          => ['CQ', 'Quality Analyst', 'Contrôle qualité', 'Quality Evaluator - Trainee', 'Contrôleur Qualité (mission)', 'CONTROLEUR QUALITE', 'Sr. Quality Evaluator', 'Quality Lead'],
        ];

        $fonctionsRecherchees = collect($categoriesSelectionnees)
            ->flatMap(fn($cat) => $fonctionMapping[$cat] ?? [])
            ->unique()->toArray();

        $selectedWeekNum = $request->input('week', now()->weekOfYear);
        $selectedYear = now()->year;
        $selectedWeekFull = $selectedYear . '-' . str_pad($selectedWeekNum, 2, '0', STR_PAD_LEFT);

        $topManager = Agent::with('projets')->where('work_email', Auth::user()->work_email)->firstOrFail();
        $projetIds = $topManager->projets->pluck('id')->toArray();

        $agents = DB::table('agents as superviseurs')
            ->join('agent_projet', 'superviseurs.id', '=', 'agent_projet.agent_id')
            ->join('projets', 'agent_projet.projet_id', '=', 'projets.id')
            ->leftJoin('agents as managers', 'superviseurs.manager', '=', 'managers.workday_id')
            ->whereIn('agent_projet.projet_id', $projetIds) 
            ->whereIn('superviseurs.fonction', $fonctionsRecherchees)
            ->select(
                'superviseurs.id', 'superviseurs.prenom', 'superviseurs.nom', 'superviseurs.fonction',
                'projets.designation as nom_projet', 
                DB::raw("COALESCE(CONCAT(managers.prenom, ' ', managers.nom), 'Direction') as nom_manager")
            )
            ->distinct()->get()->sortBy('nom_projet');

        $agentIds = $agents->pluck('id')->unique();
        $plannings = DB::table('plannings')
            ->where('semaine', $selectedWeekFull)
            ->whereIn('agent_id', $agentIds)
            ->get()
            ->map(function($item) {
                $item->entree = $item->entree ? Carbon::parse($item->entree)->format('H:i') : null;
                $item->sortie = $item->sortie ? Carbon::parse($item->sortie)->format('H:i') : null;
                return $item;
            })
            ->keyBy(fn($item) => $item->agent_id . '-' . $item->jour);

        return view('planning.index', [
            'agents' => $agents,
            'semaines' => $this->generateWeekRange($selectedYear, 0, 6),
            'selectedWeekNum' => $selectedWeekNum,
            'plannings' => $plannings,
            'categoriesDispo' => array_keys($fonctionMapping),
            'fonctionsChoisies' => $categoriesSelectionnees
        ]);
    }

    /**
     * Affiche la page de la Planification Globale (Hebdomadaire)
     */
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
            $sites = $agentConnecte ? $agentConnecte->projets->pluck('site_id')->unique() : collect();
            $projetsQuery = $agentConnecte ? $agentConnecte->projets() : Projet::where('id', 0);
        }

        $selectedSiteId = $request->input('site_id');
        $projetsList = $projetsQuery->when($selectedSiteId, fn($q) => $q->where('site_id', $selectedSiteId))->get();

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
            'selectedProjetId' => $request->input('projet_id'),
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
            $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur', 'Top Manager']) || ($user->work_email === 'admin@concentrix.com');

            $rawWeek = $request->input('week', now()->format('Y-W')); 
            $parts = explode('-', str_replace('-W', '-', $rawWeek));
            $year = (int)$parts[0];
            $weekNum = (int)($parts[1] ?? now()->weekOfYear); 

            $formatSimple = $year . '-' . $weekNum;                      
            $formatZero   = $year . '-' . str_pad($weekNum, 2, '0', STR_PAD_LEFT); 

            $dateDebut = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
            $dates = [];
            for ($i = 0; $i < 7; $i++) { $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d'); }

            $managerEmails = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'Manager')
                ->pluck('users.work_email')->toArray();

            $queryProjets = Projet::query();
            if ($isFullAccess) {
                if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
                if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') $queryProjets->where('id', $request->get('projet_id'));
            } else {
                $queryProjets->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
            }
            $projets = $queryProjets->get();

            $allPlannings = Planning::whereIn('semaine', [$formatSimple, $formatZero])->get()->groupBy('agent_id');

            $resultat = [];
            foreach ($projets as $projet) {
                $managersDuProjet = Agent::whereHas('projets', fn($q) => $q->where('projets.id', $projet->id))
                    ->whereIn('work_email', $managerEmails)->get();

                $groupes = [];
                foreach ($managersDuProjet as $agent) {
                    $boss = Agent::where('workday_id', $agent->manager)->first();
                    $bossName = $boss ? "{$boss->prenom} {$boss->nom}" : "Direction / Autre";
                    if (!isset($groupes[$bossName])) $groupes[$bossName] = ['manager' => $bossName, 'agents' => []];

                    $statsPlanning = [];
                    $agentPlannings = $allPlannings->get($agent->id) ?? collect();
                    foreach ($dates as $date) {
                        $p = $agentPlannings->first(fn($v) => date('Y-m-d', strtotime($v->jour)) === $date);
                        $statsPlanning[$date] = [
                            'in'  => ($p && $p->entree) ? date('H:i', strtotime($p->entree)) : null,
                            'out' => ($p && $p->sortie) ? date('H:i', strtotime($p->sortie)) : null,
                        ];
                    }
                    $groupes[$bossName]['agents'][] = ['nom' => $agent->nom, 'prenom' => $agent->prenom, 'fonction' => $agent->fonction ?? 'MANAGER', 'planning' => $statsPlanning];
                }
                if (!empty($groupes)) $resultat[] = ['site' => $projet->site_id, 'projet' => $projet->designation, 'groupes' => array_values($groupes)];
            }
            return response()->json(['dates' => $dates, 'resultat' => $resultat]);
        } catch (\Exception $e) {
            Log::error("API Hebdo Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Affiche la page du Graphique Journalier
     */
    public function dailyView(Request $request): View
    {
        $user = auth()->user();
        $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

        if ($isFullAccess) {
            $sites = Projet::distinct()->whereNotNull('site_id')->pluck('site_id');
            $projetsList = Projet::all();
        } else {
            $agent = Agent::with('projets')->where('work_email', $user->work_email)->first();
            $projetsList = $agent ? $agent->projets : collect();
            $sites = $projetsList->pluck('site_id')->unique();
        }

        return view('planning.daily', [
            'sites'            => $sites,
            'projetsList'      => $projetsList,
            'selectedSiteId'   => $request->input('site_id'),
            'selectedProjetId' => $request->input('projet_id'),
            'filtreFixe'       => !$isFullAccess 
        ]);
    }


    /**
     * Helpers de calcul
     */
   

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
        $request->validate(['plannings' => 'required|array', 'week' => 'required']);
        $authUserId = Auth::id(); $today = now()->startOfDay();
        $topManager = Agent::with('projets')->where('work_email', Auth::user()->work_email)->firstOrFail();
        $allowedProjetIds = $topManager->projets->pluck('id')->toArray();
        try {
            DB::beginTransaction();
            foreach ($request->input('plannings') as $agentId => $jours) {
                $agentCible = Agent::with('projets')->find($agentId);
                if (!$agentCible || empty(array_intersect($allowedProjetIds, $agentCible->projets->pluck('id')->toArray()))) continue;
                foreach ($jours as $date => $heures) {
                    if (Carbon::parse($date)->startOfDay()->isBefore($today)) continue;
                    if (!empty($heures['entree']) || !empty($heures['sortie'])) {
                        Planning::updateOrCreate(['agent_id' => $agentId, 'jour' => $date], ['entree' => $heures['entree'], 'sortie' => $heures['sortie'], 'semaine' => $request->input('week'), 'user_id' => $authUserId]);
                    }
                }
            }
            DB::commit(); return back()->with('success', 'Planning enregistré.');
        } catch (\Exception $e) { DB::rollBack(); Log::error("Store Error: " . $e->getMessage()); return back()->with('error', 'Erreur.'); }
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240', 'week' => 'required']);
        try { Excel::import(new PlanningsImport($request->week), $request->file('file')); return back()->with('success', 'Importation réussie.'); } 
        catch (\Exception $e) { Log::error("Import Error: " . $e->getMessage()); return back()->with('error', "Erreur d'importation."); }
    }


    public function getDailyPlanningData(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

            // Optimisation : Chargement groupé (Eager Loading)
            $queryProjets = Projet::with(['agents' => function($q) use ($date) {
                $q->with(['pointages' => function($pq) use ($date) {
                    $pq->whereDate('date_pointage', $date);
                }]);
            }]);

            if (!$isFullAccess) {
                $queryProjets->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
            }
            if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
            if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') $queryProjets->where('id', $request->get('projet_id'));

            $projets = $queryProjets->get();
            
            // Pré-récupération des managers pour éviter le N+1
            $managerIds = $projets->pluck('agents.*.manager')->flatten()->filter()->unique();
            $allBosses = Agent::whereIn('workday_id', $managerIds)->get()->keyBy('workday_id');

            $resultat = [];

            foreach ($projets as $projet) {
                $topManagersGroups = [];

                foreach ($projet->agents as $manager) {
                    $pointage = $manager->pointages->first();
                    if (!$pointage) continue;

                    $boss = $allBosses->get($manager->manager);
                    $topManagerName = $boss ? "{$boss->prenom} {$boss->nom}" : "Direction / Hors Groupe";

                    if (!isset($topManagersGroups[$topManagerName])) {
                        $topManagersGroups[$topManagerName] = ['top_manager' => $topManagerName, 'managers' => []];
                    }

                    // Appel de l'analyse avec les nouvelles règles
                    $analysis = $this->analyzePointage($pointage, $date);

                    $topManagersGroups[$topManagerName]['managers'][] = [
                        'nom' => "{$manager->prenom} {$manager->nom}",
                        'role' => $manager->fonction ?? 'Manager',
                        'is_connected' => $manager->last_seen >= now()->subMinutes(10),
                        'real_start' => $pointage->entree ? Carbon::parse($pointage->entree)->format('H:i') : null,
                        'real_end' => $pointage->sortie ? Carbon::parse($pointage->sortie)->format('H:i') : null,
                        'segments' => $this->calculateWorkSegments($pointage->entree, $pointage->sortie, $pointage->pause_debut, $pointage->pause_fin),
                        'pauses' => $analysis['pauses'],
                        'start_status' => $analysis['start_status'],
                        'end_status'   => $analysis['end_status'],
                        'retard_minutes' => $analysis['retard_minutes'],
                        'is_oubli' => $analysis['is_oubli']
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
            Log::error("API Journalier Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function analyzePointage($pointage, $date)
    {
        $pauses = [];
        $start_status = null;
        $end_status = null;
        $retard_minutes = 0;
        $is_oubli = false;

        // 1. Analyse du Retard (> 5 min)
        // Supposons une heure théorique de début à 08:00 (à adapter selon votre planning_theorique)
        $heure_prevue_debut = Carbon::parse($date . ' 08:00:00'); 
        $heure_entree = Carbon::parse($pointage->entree);

        if ($heure_entree->gt($heure_prevue_debut)) {
            $diff = $heure_entree->diffInMinutes($heure_prevue_debut);
            if ($diff > self::TOLERANCE_RETARD) {
                $start_status = 'RETARD';
                $retard_minutes = $diff;
            }
        }

        // 2. Analyse de la Pause (> 10 min de dépassement)
        if ($pointage->pause_debut && $pointage->pause_fin) {
            $p_start = Carbon::parse($pointage->pause_debut);
            $p_end = Carbon::parse($pointage->pause_fin);
            $duree = $p_start->diffInMinutes($p_end);
            
            $status_pause = null;
            if ($duree > (self::PAUSE_THEORIQUE + self::TOLERANCE_PAUSE)) {
                $status_pause = 'DEPASSEMENT';
            }

            $pauses[] = [
                'start' => $p_start->format('H:i'),
                'end' => $p_end->format('H:i'),
                'minutes' => $duree,
                'status' => $status_pause
            ];
        }

        // 3. Analyse Départ Anticipé / Oubli
        $heure_prevue_fin = Carbon::parse($date . ' 18:00:00'); // Exemple 18h

        if ($pointage->sortie) {
            $heure_sortie = Carbon::parse($pointage->sortie);
            if ($heure_sortie->lt($heure_prevue_fin)) {
                $end_status = 'DEPART_ANTICIPE';
            }
        } else {
            // Si pas de sortie et qu'il est déjà 19h (heure fin + 1h), on considère un oubli
            if (now()->gt($heure_prevue_fin->addHour())) {
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
}


