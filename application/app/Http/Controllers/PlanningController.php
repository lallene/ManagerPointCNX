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
    // Récupérer les fonctions sélectionnées depuis la requête (checkboxes)
    $fonctionsFiltrees = $request->input('fonctions', []);

    // Si aucune fonction sélectionnée, prendre toutes les fonctions par défaut
    if (empty($fonctionsFiltrees)) {
        $fonctionsFiltrees = ['Superviseur', 'Formateur', 'CQ'];
    }

    // Mapping des fonctions simples vers les intitulés réels en base






    $fonctionMapping = [
        'Superviseur' => [
            'Superviseur',
            'Team Leader Trainee, Operations',
            'Team Leader, Operations',
            'SUPERVISEUR',
        ],
        'Formateur' => [
            'Formateur',
            'Trainer',
           'FORMATEUR METIER',
            'Trainer I - Trainee',
            'Trainer II',
        ],
        'CQ' => [
            'CQ',
            'Quality Analyst',
            'Contrôle qualité',
            'Quality Evaluator - Trainee (Agent)',
            'Contrôleur Qualité (mission)',
            'CONTROLEUR QUALITE',
            'Sr. Quality Evaluator',
            'CONTROLEUR QUALITE PRODUCTION',
            'Contrôleur Qualité Mission',
            'Quality Lead',
        ],
    ];

    // Fusionner tous les intitulés des fonctions sélectionnées
    $fonctionsRecherchées = collect($fonctionsFiltrees)
        ->flatMap(function ($fonction) use ($fonctionMapping) {
            return $fonctionMapping[$fonction] ?? [];
        })
        ->unique()
        ->toArray();


    $selectedWeek = $request->input('week', \Carbon\Carbon::now()->weekOfYear);

    // Récupérer le manager connecté (via email)
    $user = \Illuminate\Support\Facades\Auth::user();

    // Récupérer l'agent correspondant à l'utilisateur connecté
    $agentManager = \App\Models\Agent::where('email', $user->email)->first();

    if (!$agentManager) {
        abort(404, 'Agent manager introuvable.');
    }

    // Récupérer les agents avec fonction filtrée et projet identique au manager
    $agents = \DB::table('agents as superviseurs')
        ->join('projets', 'superviseurs.projet_id', '=', 'projets.id')
        ->leftJoin('agents as managers', 'superviseurs.manager', '=', 'managers.workday_id')
        ->where('superviseurs.projet_id', $agentManager->projet_id)
        ->whereIn('superviseurs.fonction', $fonctionsRecherchées)
        ->select(
            'superviseurs.*',
            'projets.designation as nom_projet',
            \DB::raw("CONCAT(managers.nom, ' ', managers.prenom) as nom_manager")
        )
        ->get();

    // Récupérer les plannings pour la semaine sélectionnée
  $plannings = \DB::table('plannings')
    ->where('semaine', $selectedWeek)
    ->get()
    ->keyBy(function ($item) {
        return $item->agent_id . '-' . $item->jour;
    });


    // Générer la liste des semaines à afficher (exemple: semaines actuelles + 3)
    $semaines = [];
    $semaineActuelle = \Carbon\Carbon::now()->weekOfYear;
    for ($i = $semaineActuelle; $i <= $semaineActuelle + 3; $i++) {
        if ($i > 52) break;

        $start = \Carbon\Carbon::now()->startOfYear()->addWeeks($i - 1)->startOfWeek(\Carbon\Carbon::MONDAY);
        $end = (clone $start)->endOfWeek(\Carbon\Carbon::SUNDAY);

        $semaines[] = [
            'numero' => $i,
            'debut' => $start->format('d/m'),
            'fin' => $end->format('d/m'),
            'label' => "Semaine $i ({$start->format('d/m')} - {$end->format('d/m')})",
        ];
    }

    // Passer les variables à la vue
    return view('planning.index', compact('agents', 'semaines', 'selectedWeek', 'plannings'));
}





    public function store(Request $request)
    {
        foreach ($request->plannings as $agentId => $jours) {
            foreach ($jours as $date => $data) {
                if (!empty($data['heure_debut']) && !empty($data['heure_fin'])) {
                    Planning::updateOrCreate(
                        [
                            'agent_id' => $agentId,
                            'jour' => $date,
                        ],
                        [
                            'heure_debut' => $data['heure_debut'],
                            'heure_fin' => $data['heure_fin'],
                            'semaine' => $request->week,
                            'Commentaire' => $data['Commentaire'] ?? null,
                            'user_id' => auth()->id(),
                        ]
                    );
                }
            }
        }
        return redirect()->route('planification', ['week' => $request->week])
            ->with('success', 'Planning enregistré avec succès !');
    }




public function showGroupPlanning(Request $request)
{
     // 1) Fonctions superviseurs
    $fonctionsSuperviseurs = [
        'Superviseur',
        'Team Leader Trainee, Operations',
        'Team Leader, Operations',
        'SUPERVISEUR',
    ];

    // 2) Utilisateur → éventuel agent
    $user   = auth()->user();
    $agent  = Agent::where('email', $user->email)->first();   // peut être null

    // 3) Détermination (ou non) d’un filtre fixe
    $filtreFixe       = false;
    $selectedSiteId   = $request->input('site_id');
    $selectedProjetId = $request->input('projet_id');

    if ($agent && $agent->projet_id && $agent->projet_id != 113) {
        // L’agent existe et n’est PAS dans le projet 113 → on verrouille
        $projetAgent   = Projet::find($agent->projet_id);      // peut aussi être null
        if ($projetAgent) {
            $selectedSiteId   = $projetAgent->site_id;
            $selectedProjetId = $projetAgent->id;
            $filtreFixe       = true;
        }
    }


    // Requête projets avec filtres
    $projetsQuery = Projet::query();

    if ($selectedSiteId) {
        $projetsQuery->where('site_id', $selectedSiteId);
    }

    if ($selectedProjetId) {
        $projetsQuery->where('id', $selectedProjetId);
    }

    $projets = $projetsQuery->get();
    $resultat = [];

    // Groupement des projets par site
    $projetsParSite = $projets->groupBy('site_id');

    foreach ($projetsParSite as $site => $projetsSite) {
        $projetsStruct = [];

        foreach ($projetsSite as $projet) {
            $superviseurs = Agent::where('projet_id', $projet->id)
                ->whereIn('fonction', $fonctionsSuperviseurs)
                ->get();

            $groupes = [];

            foreach ($superviseurs as $agent) {
                $manager = Agent::where('workday_id', $agent->manager)->first();

                if ($manager && $manager->email) {
                    $managerKey = $manager->email;

                    if (!isset($groupes[$managerKey])) {
                        $groupes[$managerKey] = [
                            'manager' => $manager->prenom . ' ' . $manager->nom . ' (' . $manager->email . ')',
                            'superviseurs' => [],
                            'agents' => collect()
                        ];
                    }

                    $groupes[$managerKey]['superviseurs'][] =
                        $agent->prenom . ' ' . $agent->nom . ' (' . $agent->fonction . ')';
                    $groupes[$managerKey]['agents']->push($agent);
                }
            }

            $groupesFinal = [];

            foreach ($groupes as $data) {
                $groupesFinal[] = [
                    'manager' => $data['manager'],
                    'superviseurs' => $data['superviseurs'],
                    'agents' => $data['agents']
                ];
            }

            if (count($groupesFinal) > 0) {
                $projetsStruct[] = [
                    'projet' => $projet->designation,
                    'groupes' => $groupesFinal
                ];
            }
        }

        if (count($projetsStruct) > 0) {
            $resultat[] = [
                'site' => $site,
                'projets' => $projetsStruct
            ];
        }
    }

    // Semaine sélectionnée
    $selectedWeek = $request->input('week', Carbon::now()->weekOfYear);
    $dateDebut = Carbon::now()->startOfYear()->addWeeks($selectedWeek - 1)->startOfWeek(Carbon::MONDAY);
    $dateFin = (clone $dateDebut)->addDays(6);

    // Liste des jours de la semaine
    $dates = collect();
    for ($i = 0; $i < 7; $i++) {
        $dates->push($dateDebut->copy()->addDays($i)->format('Y-m-d'));
    }

    // Semaines autour de l'actuelle
    $semaines = collect();
    $semaineActuelle = now()->weekOfYear;

    for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
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

    // Plannings par agent/jour
    $plannings = Planning::where('semaine', $selectedWeek)
        ->get()
        ->groupBy(function ($item) {
            return $item->agent_id . '-' . \Carbon\Carbon::parse($item->jour)->format('Y-m-d');
        });

    // Menus déroulants (sites et projets)
    $sites = Projet::select('site_id')->distinct()->pluck('site_id');
    $projetsList = Projet::when($selectedSiteId, function ($query) use ($selectedSiteId) {
        return $query->where('site_id', $selectedSiteId);
    })->get();

    return view('planning.groupProjet', compact(
        'selectedWeek',
        'semaines',
        'dates',
        'plannings',
        'resultat',
        'sites',
        'projetsList',
        'selectedSiteId',
        'selectedProjetId',
        'filtreFixe'
    ));
}





public function showGroupPlanningDay(Request $request)
{
    $user = Auth::user();
    $agent = Agent::where('email', $user->email)->firstOrFail();


    $groupeId = $agent->manager;
    if (!$groupeId) {
        abort(403, "Vous n'êtes pas assigné à un groupe.");
    }

    $groupeIdName = Agent::where('workday_id', $agent->manager)->first();
    $groupeIdName = $groupeIdName ? $groupeIdName->nom . ' ' . $groupeIdName->prenom : '';


    $agents = Agent::where('manager', $groupeId)->get();

    // Jour sélectionné ou aujourd'hui par défaut
    $selectedDay = $request->input('date', now()->format('Y-m-d'));

    // Calcul des 7 jours de la semaine en cours (lundi → dimanche)
    $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
    $joursSemaine = collect();
    for ($i = 0; $i < 7; $i++) {
        $date = $startOfWeek->copy()->addDays($i);
        $joursSemaine->push([
            'date' => $date->format('Y-m-d'),
            'label' => $date->isoFormat('dddd'), // lundi, mardi...
        ]);
    }

    // Récupérer les plannings du jour sélectionné pour tous les agents du groupe
    $plannings = Planning::whereIn('agent_id', $agents->pluck('id'))
        ->whereDate('jour', $selectedDay)
        ->get()
        ->groupBy(function ($p) {
            return $p->agent_id . '-' . Carbon::parse($p->jour)->format('Y-m-d');
        });

    return view('planning.groupe-journee-graph', [
        'agents' => $agents,
        'selectedDay' => $selectedDay,
        'plannings' => $plannings,
        'joursSemaine' => $joursSemaine,
        'groupeId' => $groupeIdName
    ]);
}


public function planningJourneeGraphique(Request $request)
{

     // Vérifier si l'utilisateur a le rôle "manager"
    if (!Auth::user()->hasRole('Manager')) {
        abort(403, 'Accès interdit');
    }
     $user = Auth::user();
    $agent = Agent::where('email', $user->email)->firstOrFail();

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


$agentManager = \App\Models\Agent::where('email', $user->email)->first();

if (!$agentManager) {
    abort(404, 'Aucun agent trouvé avec l’e-mail : ' . $user->email);
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


}
