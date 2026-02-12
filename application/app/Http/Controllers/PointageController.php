<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Agent;
use App\Models\Pointage;
use App\Models\User;
use App\Models\Planning;
use App\Models\Projet;
use Illuminate\Http\Request;
use Carbon\Carbon;






class PointageController extends Controller
{
     /**
     * Afficher la liste des pointages (avec filtre optionnel par semaine).
     */




public function index(Request $request)
{
    $fonctionsSuperviseurs = [
        'Superviseur',
        'Team Leader Trainee, Operations',
        'Team Leader, Operations',
        'SUPERVISEUR',
    ];

    $user = auth()->user();
    $agent = Agent::where('email', $user->email)->first();

    $filtreFixe       = false;
    $selectedSiteId   = $request->input('site_id');
    $selectedProjetId = $request->input('projet_id');

    if ($agent && $agent->projet_id && $agent->projet_id != 113) {
        $projetAgent = Projet::find($agent->projet_id);
        if ($projetAgent) {
            $selectedSiteId   = $projetAgent->site_id;
            $selectedProjetId = $projetAgent->id;
            $filtreFixe       = true;
        }
    }

    $projetsQuery = Projet::query();
    if ($selectedSiteId) {
        $projetsQuery->where('site_id', $selectedSiteId);
    }
    if ($selectedProjetId) {
        $projetsQuery->where('id', $selectedProjetId);
    }
    $projets = $projetsQuery->get();

    $resultat = [];
    $projetsParSite = $projets->groupBy('site_id');

    foreach ($projetsParSite as $site => $projetsSite) {
        $projetsStruct = [];

        foreach ($projetsSite as $projet) {
            $superviseurs = Agent::where('projet_id', $projet->id)
                ->whereIn('fonction', $fonctionsSuperviseurs)
                ->get();

            $groupes = [];
            foreach ($superviseurs as $agentSup) {
                $manager = Agent::where('workday_id', $agentSup->manager)->first();

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
                        $agentSup->prenom . ' ' . $agentSup->nom . ' (' . $agentSup->fonction . ')';

                    $groupes[$managerKey]['agents']->push($agentSup);
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

    $selectedWeek = $request->input('week', Carbon::now()->weekOfYear);

    $dateDebut = Carbon::now()->startOfYear()->addWeeks($selectedWeek - 1)->startOfWeek(Carbon::MONDAY);
    $dateFin = (clone $dateDebut)->addDays(6);

    $dates = collect();
    for ($i = 0; $i < 7; $i++) {
        $dates->push($dateDebut->copy()->addDays($i)->format('Y-m-d'));
    }

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

    $pointages = Planning::select(
        'plannings.id',
        'plannings.heure_debut',
        'plannings.heure_fin',
        'plannings.jour',
        'pointages.id as pointage_id',
        'pointages.user_id',
        'pointages.semaine',
        'pointages.motif',
        'pointages.heure',
        'agents.nom as agent_nom',
        'agents.prenom as agent_prenom',
        'agents.fonction as agent_fonction',
        'projets.designation as projet_nom',
        'projets.site_id as site_id'
    )
    ->leftJoin('pointages', 'pointages.planning_id', '=', 'plannings.id')
    ->join('agents', 'plannings.agent_id', '=', 'agents.id')
    ->join('projets', 'agents.projet_id', '=', 'projets.id')
    ->whereBetween('plannings.jour', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
    ->orderBy('agents.nom')
    ->orderBy('plannings.jour')
    ->get();


 // dd($pointages);

    $pointagesParAgentDate = [];

    foreach ($pointages as $pointage) {
    // Vérification que les données de pointage sont bien présentes
    if (!$pointage->user_id || !$pointage->motif || !$pointage->heure) {


        continue; // On ignore cette ligne si les infos sont incomplètes
    }

    dd($pointage->heure, !$pointage->user_id, $pointage->motif);

    $userId = $pointage->user_id;
    $date = $pointage->jour;

    // On ne prend en compte que les dates dans la période sélectionnée
    if (!in_array($date, $dates->toArray())) {
        continue;
    }

    // Si ce jour-là pour cet utilisateur n'existe pas encore, on l'initialise
    if (!isset($pointagesParAgentDate[$userId][$date])) {
        $pointagesParAgentDate[$userId][$date] = [
            'heure_arrivee' => null,
            'heure_depart' => null,
            'heure_debut_planning' => $pointage->heure_debut,
            'heure_fin_planning' => $pointage->heure_fin,
        ];
    }



    // On remplit les heures de pointage si le motif correspond
    if ($pointage->motif === 'debut') {
        $pointagesParAgentDate[$userId][$date]['heure_arrivee'] = $pointage->heure;
    } elseif ($pointage->motif === 'fin') {
        $pointagesParAgentDate[$userId][$date]['heure_depart'] = $pointage->heure;
    }


}


    foreach ($resultat as $siteData) {
        foreach ($siteData['projets'] as $projetData) {
            foreach ($projetData['groupes'] as $groupe) {
                foreach ($groupe['agents'] as $agent) {
                    $user = User::where('email', $agent->email)->first();
                    if (!$user) continue;

                    foreach ($dates as $date) {
                        $planning = $agent->plannings->firstWhere('jour', $date);
                        if ($planning) {
                            if (!isset($pointagesParAgentDate[$user->id][$date])) {
                                $pointagesParAgentDate[$user->id][$date] = [
                                    'heure_arrivee' => null,
                                    'heure_depart' => null,
                                    'heure_debut_planning' => $planning->heure_debut,
                                    'heure_fin_planning' => $planning->heure_fin,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    $sites = Projet::select('site_id')->distinct()->pluck('site_id');
    $projetsList = Projet::when($selectedSiteId, function ($query) use ($selectedSiteId) {
        return $query->where('site_id', $selectedSiteId);
    })->get();

    return view('pointages.index', compact(
        'selectedWeek',
        'semaines',
        'dates',
        'resultat',
        'sites',
        'projetsList',
        'selectedSiteId',
        'selectedProjetId',
        'filtreFixe',
        'pointagesParAgentDate'
    ));
}







    public function create()
{
    // Vérifier si l'utilisateur a le rôle "Manager"
    if (!Auth::user()->hasRole('Manager')) {
        abort(403, 'Accès interdit');
    }

    $users = User::all();
    $plannings = Planning::all();

    $now = Carbon::now();

    $currentWeek = $now->weekOfYear;
    $currentDate = $now->toDateString();
    $currentTime = $now->format('H:i');

    // Récupérer l'historique pointages du jour pour l'utilisateur connecté
    $userId = Auth::id();
    $user = auth()->user();
    $agent = Agent::where('email', $user->email)->first();


     $planningDisponible = Planning::where('agent_id', $agent->id)
        ->whereDate('jour', $currentDate)
        ->where('semaine', $currentWeek)
        ->get();

      //  dd($planningDisponible);



    $pointagesDuJour = Pointage::where('user_id', $userId)
                        ->whereDate('date', $currentDate)
                        ->orderBy('heure')
                        ->get();

    $debutEnregistre = $pointagesDuJour->contains('motif', 'debut');
    $motifsEnregistres = $pointagesDuJour->pluck('motif')->toArray();
    $dernierPointage = $pointagesDuJour->last();

    $ordreActions = ['debut', 'debutpause', 'finpause', 'fin'];
    $actionsEffectuees = $pointagesDuJour->pluck('motif')->toArray();
    $prochaineAction = null;

    foreach ($ordreActions as $action) {
        if (!in_array($action, $actionsEffectuees)) {
            $prochaineAction = $action;
            break;
        }
}




    return view('pointages.create', compact(
        'users', 'plannings', 'currentWeek', 'currentDate', 'currentTime', 'pointagesDuJour', 'planningDisponible', 'debutEnregistre', 'motifsEnregistres', 'prochaineAction'
    ));
}

    /**
     * Enregistrer un nouveau pointage.
     */
    public function store(Request $request)
    {

    if (in_array($request->action, ['debutpause', 'finpause', 'fin'])) {
    $hasDebut = Pointage::where('user_id', auth()->id())
                        ->where('date', $request->date)
                        ->where('motif', 'debut')
                        ->exists();

    if (!$hasDebut) {
        return back()->withErrors(['Vous devez enregistrer le début de shift avant cette action.']);
    }
    }
    $user = Auth::user();
    $now = Carbon::now();

        // Valider uniquement l'action qui vient du formulaire
    $request->validate([
        'action' => 'required|in:debut,finpause,debutpause,fin',
    ]);

        $idAgent = $user->id;
        $semaine = $now->weekOfYear;
        $jour = $jour = $now->toDateString();
        $agent_id = Agent::where ('email', $user->email)->first();
         $agent_id = $agent_id->id;


        // Exemple : chercher le planning où agent = $idAgent, semaine = $semaine, et date = $jour
        $planning = Planning::where('agent_id', $agent_id)
                        ->where('semaine', $semaine)
                        ->whereDate('jour', $jour)
                        ->first();

        if ($planning) {
            $planningId = $planning->id;
            // faire quelque chose avec $planningId
        } else {
            // aucun planning trouvé pour ces critères
            $planningId = null;
        }




    // Créer le pointage avec données serveur
    Pointage::create([
        'user_id' => $user->id,
        'semaine' => $now->weekOfYear,
        'date' => $now->toDateString(),
        'heure' => $now->format('H:i:s'),
        'motif' => $request->input('action'),
        'planning_id' => $planningId,
    ]);


    return redirect()->route('pointages.create')
                     ->with('success', 'Pointage enregistré avec succès.');



    }

    /**
     * Modifier un pointage existant.
     */
    public function update(Request $request, Pointage $pointage)
    {
        $request->validate([
            'semaine' => 'required|integer',
            'date' => 'required|date',
            'heure' => 'required',
            'motif' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'planning_id' => 'required|exists:plannings,id',
        ]);

        $pointage->update($request->all());

        return redirect()->back()->with('success', 'Pointage mis à jour.');
    }

    /**
     * Supprimer un pointage.
     */
    public function destroy(Pointage $pointage)
    {
        $pointage->delete();

        return redirect()->back()->with('success', 'Pointage supprimé.');
    }

    public function PointageGlobal(Request $request)
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

    $pointages = Pointage::select(
        'pointages.id',
        'pointages.date',
        'pointages.planning_id as planning_id',
        'pointages.user_id',
        'pointages.semaine',
        'pointages.motif',
        'pointages.heure',
        'agents.nom as agent_nom',
        'agents.prenom as agent_prenom',
        'agents.fonction as agent_fonction',
        'projets.designation as projet_nom',
        'projets.site_id as site_id'
    )
    ->leftJoin('plannings', 'pointages.planning_id', '=', 'plannings.id')
    ->join('users', 'pointages.user_id', '=', 'users.id')

    ->join('agents', 'users.email', '=', 'agents.email')
    ->join('projets', 'agents.projet_id', '=', 'projets.id')
    ->whereBetween('pointages.date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
    ->orderBy('agents.nom')
    ->orderBy('pointages.date')
    ->get();

  //  dd($pointages);

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


    return view('pointages.group', compact(
        'selectedWeek',
        'semaines',
        'dates',
        'plannings',
        'resultat',
        'sites',
        'projetsList',
        'selectedSiteId',
        'selectedProjetId',
        'filtreFixe',
        'pointages'
    ));
}
}
