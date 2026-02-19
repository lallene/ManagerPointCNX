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
     * API : Données pour le graphique journalier
     */
    public function getDailyPlanningData(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

            $queryProjets = Projet::query();
            if (!$isFullAccess) {
                $queryProjets->whereHas('agents', fn($q) => $q->where('work_email', $user->work_email));
            }
            if ($request->filled('site_id')) $queryProjets->where('site_id', $request->get('site_id'));
            if ($request->filled('projet_id') && $request->get('projet_id') !== 'null') $queryProjets->where('id', $request->get('projet_id'));

            $projets = $queryProjets->get();
            $resultat = [];

            foreach ($projets as $projet) {
                $allManagers = Agent::whereHas('projets', fn($q) => $q->where('projets.id', $projet->id))->get();
                $topManagersGroups = [];

                foreach ($allManagers as $manager) {
                    $boss = Agent::where('workday_id', $manager->manager)->first();
                    $topManagerName = $boss ? "{$boss->prenom} {$boss->nom}" : "Direction / Hors Groupe";

                    $pointage = DB::table('pointages')->where('agent_id', $manager->id)->whereDate('date_pointage', $date)->first();
                    if (!$pointage) continue;

                    if (!isset($topManagersGroups[$topManagerName])) $topManagersGroups[$topManagerName] = ['top_manager' => $topManagerName, 'managers' => []];

                    $analysis = $this->analyzePointage($pointage, $date);
                    $topManagersGroups[$topManagerName]['managers'][] = [
                        'nom' => "{$manager->prenom} {$manager->nom}",
                        'role' => $manager->fonction ?? 'Manager',
                        'is_connected' => $manager->last_seen >= now()->subMinutes(10),
                        'real_start' => $pointage->entree ? Carbon::parse($pointage->entree)->format('H:i') : null,
                        'segments' => $this->calculateWorkSegments($pointage->entree, $pointage->sortie, $pointage->pause_debut, $pointage->pause_fin),
                        'pauses' => $analysis['pauses'],
                        'start_status' => $analysis['start_status'],
                        'end_status'   => $analysis['end_status'],
                        'retard_minutes' => $analysis['retard_minutes']
                    ];
                }

                if (!empty($topManagersGroups)) {
                    $resultat[] = ['id_projet' => $projet->id, 'site' => $projet->site_id, 'projet' => $projet->designation, 'top_managers' => array_values($topManagersGroups)];
                }
            }
            return response()->json($resultat);
        } catch (\Exception $e) {
            Log::error("API Journalier Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helpers de calcul
     */
    private function analyzePointage($pointage, $date): array
    {
        $PAUSE_NORMAL = 60; $TOLERANCE = 5; $retardMinutes = 0;
        $pauseMinutes = 0; $pauseStatus = null;
        if ($pointage->pause_debut && $pointage->pause_fin) {
            $pauseMinutes = Carbon::parse($pointage->pause_debut)->diffInMinutes(Carbon::parse($pointage->pause_fin));
            if ($pauseMinutes > ($PAUSE_NORMAL + $TOLERANCE)) $pauseStatus = 'DEPASSEMENT';
        }
        $startStatus = null;
        if ($pointage->entree) {
            $shiftStart = Carbon::parse($date . ' 06:00');
            $diff = $shiftStart->diffInMinutes(Carbon::parse($pointage->entree), false);
            if ($diff > $TOLERANCE) { $startStatus = 'RETARD'; $retardMinutes = $diff; }
        }
        $endStatus = null;
        if ($pointage->sortie) {
            $shiftEnd = Carbon::parse($date . ' 18:00');
            if (Carbon::parse($pointage->sortie)->lt($shiftEnd)) $endStatus = 'DEPART_ANTICIPE';
        }
        return [
            'pauses' => ($pointage->pause_debut && $pointage->pause_fin) ? [['start' => Carbon::parse($pointage->pause_debut)->format('H:i'), 'end' => Carbon::parse($pointage->pause_fin)->format('H:i'), 'minutes' => $pauseMinutes, 'status' => $pauseStatus]] : [],
            'start_status' => $startStatus, 'end_status' => $endStatus, 'retard_minutes' => $retardMinutes
        ];
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
}