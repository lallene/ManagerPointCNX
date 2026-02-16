<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Agent;
use App\Models\Projet;
use App\Models\Planning;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Imports\PlanningImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;


class PlanningController extends Controller

{

    public function index(Request $request)
{
    // 1. Récupérer les catégories sélectionnées (checkboxes)
    $categoriesSelectionnees = $request->input('fonctions', []);

    // Si aucune sélection, on met les catégories par défaut
    if (empty($categoriesSelectionnees)) {
        $categoriesSelectionnees = ['Superviseur', 'Formateur', 'CQ'];
    }

    // 2. Le Mapping : Catégorie -> Intitulés réels en base
    $fonctionMapping = [
        'Superviseur' => ['Superviseur', 'Team Leader Trainee, Operations', 'Team Leader, Operations', 'SUPERVISEUR'],
        'Formateur' => ['Formateur', 'Trainer', 'FORMATEUR METIER', 'Trainer I - Trainee', 'Trainer II'],
        'CQ' => [
            'CQ', 'Quality Analyst', 'Contrôle qualité', 'Quality Evaluator - Trainee (Agent)', 
            'Contrôleur Qualité (mission)', 'CONTROLEUR QUALITE', 'Sr. Quality Evaluator', 
            'CONTROLEUR QUALITE PRODUCTION', 'Contrôleur Qualité Mission', 'Quality Lead'
        ],
    ];

    // On transforme les catégories choisies en une liste plate d'intitulés SQL
    $fonctionsRecherchees = collect($categoriesSelectionnees)
        ->flatMap(fn($cat) => $fonctionMapping[$cat] ?? [])
        ->unique()
        ->toArray();

    // 3. Gestion de la semaine
    $now = \Carbon\Carbon::now();
    $selectedWeekNum = $request->input('week', $now->weekOfYear);
    $selectedYear = $now->year;
    $selectedWeekFull = $selectedYear . '-' . str_pad($selectedWeekNum, 2, '0', STR_PAD_LEFT);

    // 4. Identification du Manager
    $user = \Illuminate\Support\Facades\Auth::user();
    $agentManager = \App\Models\Agent::where('work_email', $user->work_email)->first();

    if (!$agentManager) {
        abort(404, 'Profil agent manager introuvable.');
    }

    // 5. Requête des Agents (Filtrage par les intitulés mappés)
    $agents = \DB::table('agents as superviseurs')
        ->join('projets', 'superviseurs.projet_id', '=', 'projets.id')
        ->leftJoin('agents as managers', 'superviseurs.manager', '=', 'managers.workday_id')
        ->where('superviseurs.projet_id', $agentManager->projet_id)
        ->whereIn('superviseurs.fonction', $fonctionsRecherchees) // Utilise la liste mappée
        ->select(
            'superviseurs.*',
            'projets.designation as nom_projet',
            \DB::raw("CONCAT(managers.nom, ' ', managers.prenom) as nom_manager")
        )
        ->get();

    // 6. Récupération des plannings
    $plannings = \DB::table('plannings')
        ->where('semaine', $selectedWeekFull)
        ->get()
        ->keyBy(fn($item) => $item->agent_id . '-' . $item->jour);

    // 7. Génération des semaines pour le filtre
    $semaines = [];
    $semaineActuelle = $now->weekOfYear;
    for ($i = $semaineActuelle; $i <= $semaineActuelle + 3; $i++) {
        if ($i > 52) break;
        $start = \Carbon\Carbon::now()->setISODate($selectedYear, $i)->startOfWeek();
        $end = (clone $start)->endOfWeek();
        $semaines[] = [
            'numero' => $i,
            'debut' => $start->format('d/m'),
            'fin' => $end->format('d/m'),
        ];
    }

    return view('planning.index', [
        'agents'            => $agents,
        'semaines'          => $semaines,
        'selectedWeekNum'   => $selectedWeekNum,
        'plannings'         => $plannings,
        'categoriesDispo'   => array_keys($fonctionMapping), // 'Superviseur', 'Formateur', 'CQ'
        'fonctionsChoisies' => $categoriesSelectionnees
    ]);
}


    public function planningJourneeGraphique(Request $request)
    {

        // Vérifier si l'utilisateur a le rôle "manager"
        if (!Auth::user()->hasRole('Manager')) {
            abort(403, 'Accès interdit');
        }
        $user = Auth::user();
        $agent = Agent::where('work_email', $user->work_email)->firstOrFail();

        $week = $request->input('week', now()->isoWeek());
        $year = now()->year;

        // Calcul des dates de la semaine choisie
        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $dates = collect();
        for ($i = 0; $i < 7; $i++) {
            $dates->push($startOfWeek->copy()->addDays($i));
        }

        // Récupération des plannings de l'agent pour cette semaine
        $plannings = Planning::where('agent_id', $agent->id)
            ->whereBetween('jour', [$dates->first()->format('Y-m-d'), $dates->last()->format('Y-m-d')])
            ->get()
            ->groupBy(function ($p) {
                return Carbon::parse($p->jour)->format('Y-m-d');
            });

        // Données de la semaine à afficher sous forme de boutons
        $semaines = collect();

        // Semaine actuelle
        $semaineActuelle = now()->weekOfYear;
            // Liste de toutes les semaines de l'année (utile pour les boutons de navigation)

        // Boucle de (actuelle - 3) à (actuelle + 2)
            for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
                // Vérifie que le numéro de semaine est valide (>=1 et <= 53)
                if ($i < 1 || $i > 53) continue;

                $start = Carbon::now()->startOfYear()->addWeeks($i - 1)->startOfWeek(Carbon::MONDAY);
                $end = (clone $start)->addDays(6);

                $semaines->push([
                    'numero' => $i,
                    'debut' => $start->format('d/m'),
                    'fin' => $end->format('d/m'),
                    'label' => "Semaine $i ({$start->format('d/m')} - {$end->format('d/m')})"
                ]);
            }

            return view('planning.journee-graph', [
                'dates' => $dates,
                'semaines' => $semaines,
                'selectedWeek' => $week,
                'plannings' => $plannings,
                'agent' => $agent
            ]);


    }


    public function PlanningGlobal(Request $request)
    {

        $fonctionsSuperviseurs = [
            'Superviseur',
            'Team Leader Trainee, Operations',
            'Team Leader, Operations',
            'SUPERVISEUR'
        ];


            // Semaine sélectionnée ou semaine actuelle
        $selectedWeek = $request->input('week', Carbon::now()->weekOfYear);

        // Récupérer la date de début et de fin de la semaine
        $dateDebut = Carbon::now()->startOfYear()->addWeeks($selectedWeek - 1)->startOfWeek(Carbon::MONDAY);
        $dateFin = (clone $dateDebut)->addDays(6);

        $user = Auth::user();
        $firstRoleName = $user->roles->first()->name ?? 'Aucun rôle';


        $agentManager = \App\Models\Agent::where('work_email', $user->work_email)->first();

        if (!$agentManager) {
            abort(404, 'Aucun agent trouvé avec l’e-mail : ' . $user->work_email);
        }
            $agents = Agent::where('projet_id', $agentManager->projet_id)
                        ->whereIn('fonction', $fonctionsSuperviseurs)
                    ->get();

        // Créer la liste des dates de la semaine (pour les en-têtes)
        $dates = collect();
        for ($i = 0; $i < 7; $i++) {
            $dates->push($dateDebut->copy()->addDays($i)->format('Y-m-d'));
        }

        $semaines = collect();

        // Semaine actuelle
        $semaineActuelle = now()->weekOfYear;
            // Liste de toutes les semaines de l'année (utile pour les boutons de navigation)

        // Boucle de (actuelle - 3) à (actuelle + 2)
            for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
                // Vérifie que le numéro de semaine est valide (>=1 et <= 53)
                if ($i < 1 || $i > 53) continue;

                $start = Carbon::now()->startOfYear()->addWeeks($i - 1)->startOfWeek(Carbon::MONDAY);
                $end = (clone $start)->addDays(6);

                $semaines->push([
                    'numero' => $i,
                    'debut' => $start->format('d/m'),
                    'fin' => $end->format('d/m'),
                    'label' => "Semaine $i ({$start->format('d/m')} - {$end->format('d/m')})"
                ]);
            }


            // Récupérer les plannings de la semaine, groupés par agent_id + jour
            $plannings = Planning::where('semaine', $selectedWeek)
            ->get()
            ->groupBy(function ($item) {
                return $item->agent_id . '-' . \Carbon\Carbon::parse($item->jour)->format('Y-m-d');
            });



        // dd($plannings);
            return view('planning.group', compact(
                'selectedWeek',
                'semaines',
                'dates',
                'agents',
                'plannings'
            ));

        }



        public function import(Request $request)
        {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls',
                'week' => 'required'
            ]);

            Excel::import(new PlanningImport($request->week), $request->file('file'));

            return redirect()->route('planification', ['week' => $request->week])
                ->with('success', 'Planning importé avec succès !');
    }


    public function showGroupPlanningView(Request $request)
    {
        $sites = Projet::select('site_id')->distinct()->pluck('site_id');
        $projetsList = Projet::all();
        
        // On réutilise ta logique de semaines pour l'affichage des boutons
        $semaines = collect();
        $semaineActuelle = now()->weekOfYear;
        for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
            $start = Carbon::now()->startOfYear()->addWeeks($i - 1)->startOfWeek(Carbon::MONDAY);
            $semaines->push(['numero' => $i, 'debut' => $start->format('d/m')]);
        }

        return view('planning.groupProjet', [
            'sites' => $sites,
            'projetsList' => $projetsList,
            'semaines' => $semaines,
            'selectedWeek' => $request->input('week', now()->weekOfYear),
            'selectedSiteId' => $request->input('site_id'),
            'selectedProjetId' => $request->input('projet_id'),
            'filtreFixe' => false // ou ta logique de verrouillage
        ]);
    }

    public function getPlanningData(Request $request)
    {
        $fonctionsSuperviseurs = ['Superviseur', 'Team Leader Trainee, Operations', 'Team Leader, Operations', 'SUPERVISEUR'];

        // 1. Gestion de la temporalité (Formatage BDD : 2026-07)
        $weekNum = $request->input('week', Carbon::now()->weekOfYear);
        $year = Carbon::now()->year; 
        $selectedWeekDB = $year . '-' . str_pad($weekNum, 2, '0', STR_PAD_LEFT);

        // 2. Filtres et Sécurité
        $user = auth()->user();
        $agentConnecte = Agent::where('work_email', $user->work_email)->first();
        $selectedSiteId = $request->input('site_id');
        $selectedProjetId = $request->input('projet_id');

        if ($agentConnecte && $agentConnecte->projet_id && $agentConnecte->projet_id != 113) {
            $projetAgent = Projet::find($agentConnecte->projet_id);
            if ($projetAgent) {
                $selectedSiteId = $projetAgent->site_id;
                $selectedProjetId = $projetAgent->id;
            }
        }

        // 3. Génération des dates de la semaine
        $dateDebut = Carbon::now()->setISODate($year, $weekNum)->startOfWeek(Carbon::MONDAY);
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d');
        }

        // 4. Récupération des plannings (Groupés par agent)
        $allPlannings = Planning::where('semaine', $selectedWeekDB)
            ->get()
            ->groupBy('agent_id');

        // 5. Construction de la hiérarchie JSON
        $projets = Projet::when($selectedSiteId, function($q) use ($selectedSiteId) {
                return $q->where('site_id', $selectedSiteId);
            })
            ->when($selectedProjetId, function($q) use ($selectedProjetId) {
                return $q->where('id', $selectedProjetId);
            })
            ->get();

        $resultat = [];
        foreach ($projets as $projet) {
            $superviseurs = Agent::where('projet_id', $projet->id)
                ->whereIn('fonction', $fonctionsSuperviseurs)
                ->get();

            $groupes = [];
            foreach ($superviseurs as $sup) {
                $manager = Agent::where('workday_id', $sup->manager)->first();
                $managerKey = $manager ? $manager->work_email : 'sans_manager';
                
                if (!isset($groupes[$managerKey])) {
                    $groupes[$managerKey] = [
                        'manager' => $manager ? "{$manager->prenom} {$manager->nom}" : "Direction / Inconnu",
                        'agents'  => []
                    ];
                }

                $statsPlanning = [];
                $agentPlannings = $allPlannings->get($sup->id) ?? collect();

                foreach ($dates as $date) {
                    $p = $agentPlannings->first(function($value) use ($date) {
                        return Carbon::parse($value->jour)->format('Y-m-d') === $date;
                    });

                    $statsPlanning[$date] = [
                        'in'  => ($p && $p->entree) ? Carbon::parse($p->entree)->format('H:i') : null,
                        'out' => ($p && $p->sortie) ? Carbon::parse($p->sortie)->format('H:i') : null,
                    ];
                }

                $groupes[$managerKey]['agents'][] = [
                    'nom'      => $sup->nom,
                    'prenom'   => $sup->prenom,
                    'fonction' => $sup->fonction,
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
            'resultat' => $resultat,
            'week'     => $selectedWeekDB
        ]);
    }


}


