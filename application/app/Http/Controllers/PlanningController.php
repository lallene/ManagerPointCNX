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
use App\Imports\PlanningImport;


class PlanningController extends Controller
{
   
    public function index(Request $request): View
    {
        $categoriesSelectionnees = $request->input('fonctions', ['Superviseur', 'Formateur', 'CQ']);

        $fonctionMapping = [
            'Superviseur' => ['Superviseur', 'Team Leader Trainee, Operations', 'Team Leader, Operations', 'SUPERVISEUR'],
            'Formateur' => ['Formateur', 'Trainer', 'FORMATEUR METIER', 'Trainer I - Trainee', 'Trainer II'],
            'CQ' => ['CQ', 'Quality Analyst', 'Contrôle qualité', 'Quality Evaluator - Trainee', 'Contrôleur Qualité (mission)', 'CONTROLEUR QUALITE', 'Sr. Quality Evaluator', 'Quality Lead'],
        ];
        $fonctionsRecherchees = collect($categoriesSelectionnees)
            ->flatMap(fn($cat) => $fonctionMapping[$cat] ?? [])
            ->unique()->toArray();
        $selectedWeekNum = $request->input('week', now()->weekOfYear);
        $selectedYear = now()->year;
        $selectedWeekFull = $selectedYear . '-' . str_pad($selectedWeekNum, 2, '0', STR_PAD_LEFT);
        $agentManager = Agent::where('work_email', Auth::user()->work_email)->firstOrFail();
        $agents = DB::table('agents as superviseurs')
            ->join('agent_projet', 'superviseurs.id', '=', 'agent_projet.agent_id')
            ->join('projets', 'agent_projet.projet_id', '=', 'projets.id')
            ->leftJoin('agents as managers', 'superviseurs.manager', '=', 'managers.workday_id')
            ->where('agent_projet.projet_id', $agentManager->projet_id)
            ->whereIn('superviseurs.fonction', $fonctionsRecherchees)
            ->select(
                'superviseurs.*', 
                'projets.designation as nom_projet', 
                DB::raw("CONCAT(managers.nom, ' ', managers.prenom) as nom_manager")
            )
            ->get();

            dd($agents);
        $plannings = DB::table('plannings')
            ->where('semaine', $selectedWeekFull)
            ->get()->keyBy(fn($item) => $item->agent_id . '-' . $item->jour);

        return view('planning.index', [
            'agents' => $agents,
            'semaines' => $this->generateWeekRange($selectedYear, 0, 4),
            'selectedWeekNum' => $selectedWeekNum,
            'plannings' => $plannings,
            'categoriesDispo' => array_keys($fonctionMapping),
            'fonctionsChoisies' => $categoriesSelectionnees
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'week' => 'required'
        ]);

        try {
            Excel::import(new PlanningImport($request->week), $request->file('file'));
            return redirect()->back()->with('success', 'Planning importé avec succès !');
        } catch (\Exception $e) {
            Log::error("Erreur import planning: " . $e->getMessage());
            return redirect()->back()->with('error', "Erreur lors de l'import.");
        }
    }

    public function PlanningGlobal(Request $request): View
    {
        $selectedWeek = $request->input('week', now()->format('Y-W')); 
        $parts = explode('-', $selectedWeek);
        $year = $parts[0];
        $weekNum = $parts[1] ?? now()->weekOfYear;
        $dateDebut = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();

        $user = Auth::user();
        $isAdmin = ($user->work_email === 'admin@concentrix.com');

        $selectedSiteId = $request->input('site_id');
        $selectedProjetId = $request->input('projet_id');

        // Récupération des sites via Projets
        $sites = Projet::distinct()->whereNotNull('site_id')->pluck('site_id');
        
        // Liste des projets filtrée si site sélectionné
        $projets = Projet::when($selectedSiteId, function($q) use ($selectedSiteId) {
            return $q->where('site_id', $selectedSiteId);
        })->get();

        // Génération de la navigation (7 semaines)
        $semaines = collect();
        $currentWeekNum = now()->weekOfYear;
        for ($i = $currentWeekNum - 3; $i <= $currentWeekNum + 3; $i++) {
            if ($i < 1 || $i > 53) continue;
            $start = Carbon::now()->setISODate($year, $i)->startOfWeek();
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
            'projetsList'      => $projets,
            'selectedSiteId'   => $selectedSiteId,
            'selectedProjetId' => $selectedProjetId,
            'filtreFixe'       => !$isAdmin
        ]);
    }
    /**
     * Récupération des données pour le planning global
     */
    public function getPlanningData(Request $request): JsonResponse
{
    try {
        $user = Auth::user();
        
        $fullAccessRoles = ['IT', 'RH', 'Directeur', 'Top Manager'];
        $hasFullAccess = $user->hasAnyRole($fullAccessRoles) || ($user->work_email === 'admin@concentrix.com');

        // Correction du bug de l'année "0008"
        $rawWeek = $request->input('week', now()->format('Y-W')); 
        $parts = explode('-', str_replace('-W', '-', $rawWeek));
        $year = (int)$parts[0]; // Sécurité : force l'entier
        $weekNum = (int)($parts[1] ?? now()->weekOfYear); 

        $formatSimple = $year . '-' . $weekNum;                      
        $formatZero   = $year . '-' . str_pad($weekNum, 2, '0', STR_PAD_LEFT); 

        $dateDebut = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d');
        }

        $managerEmails = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Manager')
            ->pluck('users.work_email')
            ->toArray();

        // Logique de récupération des projets (Pivot compatible)
        if ($hasFullAccess) {
            $selectedSiteId = $request->input('site_id');
            $selectedProjetId = $request->input('projet_id');

            $projets = Projet::when($selectedSiteId, fn($q) => $q->where('site_id', $selectedSiteId))
                ->when($selectedProjetId, fn($q) => $q->where('id', $selectedProjetId))
                ->get();
        } else {
            $agentConnecte = Agent::with('projets')->where('work_email', $user->work_email)->first();
            if (!$agentConnecte) {
                return response()->json(['error' => 'Profil agent introuvable.'], 403);
            }
            // On récupère la collection de projets via la relation pivot
            $projets = $agentConnecte->projets; 
        }

        $allPlannings = \App\Models\Planning::whereIn('semaine', [$formatSimple, $formatZero])
            ->get()
            ->groupBy('agent_id');

        $resultat = [];

        foreach ($projets as $projet) {
            // FIX : Utilisation de whereHas pour filtrer les managers du projet via la pivot
            $managersDuProjet = Agent::whereHas('projets', function($q) use ($projet) {
                $q->where('projets.id', $projet->id);
            })
            ->whereIn('work_email', $managerEmails)
            ->get();

            $groupes = [];
            foreach ($managersDuProjet as $agent) {
                $boss = Agent::where('workday_id', $agent->manager)->first();
                $bossName = $boss ? "{$boss->prenom} {$boss->nom}" : "Direction / Autre";

                if (!isset($groupes[$bossName])) {
                    $groupes[$bossName] = ['manager' => $bossName, 'agents' => []];
                }

                $statsPlanning = [];
                $agentPlannings = $allPlannings->get($agent->id) ?? collect();

                foreach ($dates as $date) {
                    $p = $agentPlannings->first(function($v) use ($date) {
                        return date('Y-m-d', strtotime($v->jour)) === $date;
                    });

                    $statsPlanning[$date] = [
                        'in'  => ($p && $p->entree) ? date('H:i', strtotime($p->entree)) : null,
                        'out' => ($p && $p->sortie) ? date('H:i', strtotime($p->sortie)) : null,
                    ];
                }

                $groupes[$bossName]['agents'][] = [
                    'nom'      => $agent->nom,
                    'prenom'   => $agent->prenom,
                    'fonction' => $agent->fonction ?? 'MANAGER',
                    'planning' => $statsPlanning
                ];
            }

            if (count($groupes) > 0) {
                $resultat[] = [
                    'site'    => $projet->site_id,
                    'projet'  => $projet->designation,
                    'groupes' => array_values($groupes)
                ];
            }
        }

        return response()->json([
            'dates'    => $dates,
            'resultat' => $resultat
        ]);

    } catch (\Exception $e) {
        // Log l'erreur pour le debug Docker
        \Log::error("Planning Error: " . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}