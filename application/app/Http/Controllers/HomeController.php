<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use Carbon\Carbon;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */


   public function index(Request $request)
    {
        $currentWeek = Carbon::now()->isoWeek();
        $selectedWeek = $request->input('week', $currentWeek);

        // Générer la liste des semaines à afficher (exemple: semaines actuelles -3 à +3)
        $semaines = [];
        $semaineActuelle = Carbon::now()->weekOfYear;

        for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
            if ($i < 1 || $i > 52) continue;

            $start = Carbon::now()->startOfYear()->addWeeks($i - 1)->startOfWeek(Carbon::MONDAY);
            $end = (clone $start)->endOfWeek(Carbon::SUNDAY);

            $semaines[] = [
                'numero' => $i,
                'debut' => $start->format('d/m'),
                'fin' => $end->format('d/m'),
                'label' => "Semaine $i ({$start->format('d/m')} - {$end->format('d/m')})",
            ];
        }

        return view('home', [
            'selectedWeek' => $selectedWeek,
            'semaines' => $semaines
        ]);
    }

public function decimalToTime($decimalHours) {
    if ($decimalHours === null) {
        return null;
    }

    $totalSeconds = (int) round($decimalHours * 3600);
    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}


    public function ajaxData(Request $request)
    {
        $data = DB::table('agents')
            ->leftJoin('projets', 'agents.projet_id', '=', 'projets.id')
            ->leftJoin('sites', 'projets.site_id', '=', 'sites.id')
            ->leftJoin('agents as managers', 'agents.manager', '=', 'managers.workday_id')
            ->leftJoin('plannings', 'agents.id', '=', 'plannings.agent_id')
            ->leftJoin('users as planificateurs', 'plannings.user_id', '=', 'planificateurs.id')
            ->leftJoin('pointages', function ($join) {
                $join->on('plannings.id', '=', 'pointages.planning_id');
            })
            ->select(
                'agents.workday_id',
                DB::raw("CONCAT(agents.nom, ' ', agents.prenom) as nom_prenom"),
                'agents.email',
                'agents.fonction',
                'sites.designation as site',
                'projets.designation as projet',
                DB::raw("COALESCE(CONCAT(managers.prenom, ' ', managers.nom), agents.manager) as manager"),
                DB::raw("CONCAT(plannings.heure_debut, ' - ', plannings.heure_fin) as planning"),
                'planificateurs.name as user_planificateur',
                'plannings.updated_at as date_planification',
                'plannings.jour',
                'plannings.semaine',

                DB::raw("MAX(CASE WHEN pointages.motif = 'debut' THEN pointages.heure END) as debut_shift_pointe"),
                DB::raw("MAX(CASE WHEN pointages.motif = 'fin' THEN pointages.heure END) as fin_shift_pointe"),
                DB::raw("MAX(CASE WHEN pointages.motif = 'debutpause' THEN pointages.heure END) as debut_pause_pointe"),
                DB::raw("MAX(CASE WHEN pointages.motif = 'finpause' THEN pointages.heure END) as fin_pause_pointe")
            )
            ->whereNotNull('agents.workday_id')
            ->whereNotNull('plannings.heure_debut')
            ->groupBy(
                'agents.workday_id',
                'agents.nom',
                'agents.prenom',
                'agents.email',
                'agents.fonction',
                'sites.designation',
                'projets.designation',
                'managers.prenom',
                'managers.nom',
                'agents.manager',
                'plannings.heure_debut',
                'plannings.heure_fin',
                'planificateurs.name',
                'plannings.updated_at',
                'plannings.jour',
                'plannings.semaine'
            )
            ->get();

    // Filtrer plannings null ou < 8h
        $data = $data->filter(function ($item) {
            if (!$item->planning) return false;

            $heures = explode(' - ', $item->planning);
            $debut = strtotime($heures[0]);
            $fin   = strtotime($heures[1]);

            return ($fin - $debut) / 3600 >= 8;
        });
        // Post-traitement

        $data = $data->map(function ($item) {
        $toTime = function ($value) {
            return $value ? strtotime($value) : null;
        };

        $heureDebut    = $toTime($item->debut_shift_pointe);
        $heureFin      = $toTime($item->fin_shift_pointe);
        $pauseDebut    = $toTime($item->debut_pause_pointe);
        $pauseFin      = $toTime($item->fin_pause_pointe);

        // Planning
        $planningDebut = $item->planning ? $toTime(explode(' - ', $item->planning)[0]) : null;
        $planningFin   = $item->planning ? $toTime(explode(' - ', $item->planning)[1]) : null;

        // Ajuster les heures pointées pour qu'elles restent dans le planning
        if ($heureDebut && $planningDebut) {
            $heureDebut = max($heureDebut, $planningDebut);
        }
        if ($heureFin && $planningFin) {
            $heureFin = min($heureFin, $planningFin);
        }

        // Calcul heures travail
        $heuresTravail = ($heureDebut && $heureFin)
            ? max(0, round(($heureFin - $heureDebut - (($pauseDebut && $pauseFin) ? ($pauseFin - $pauseDebut) : 0)) / 3600, 2))
            : 0;

        // Limiter à 15h max
        $heuresTravail = min($heuresTravail, 15);

        // Calcul pause
        $heuresPause = ($pauseDebut && $pauseFin)
            ? max(0, round(($pauseFin - $pauseDebut) / 3600, 2))
            : 0;

        // Heures absentes
    // Heures absentes
    if ($planningDebut && $planningFin) {
        if ($heureDebut) {
            // Début pointé avant ou après planning : limiter entre planningDebut et planningFin
            $debutEffectif = max($planningDebut, min($heureDebut, $planningFin));
            $heuresAbsentes = max(0, round(($debutEffectif - $planningDebut) / 3600, 2));
        } else {
            // Absence totale = durée du planning
            $heuresAbsentes = max(0, round(($planningFin - $planningDebut) / 3600, 2));
        }
    } else {
        $heuresAbsentes = 0;
    }


        // Conversion en HH:MM:SS
        $item->nombre_heures_travail  = $this->decimalToTime($heuresTravail);
        $item->nombre_heures_pause    = $this->decimalToTime($heuresPause);
        $item->nombre_heures_absentes = $this->decimalToTime($heuresAbsentes);

        return $item;
    });





        return datatables()->of($data)->make(true);
    }



public function weeklyReport()
{
    $data = DB::table('agents')
        ->leftJoin('projets', 'agents.projet_id', '=', 'projets.id')
        ->leftJoin('plannings', 'agents.id', '=', 'plannings.agent_id')
        ->leftJoin('pointages', 'plannings.id', '=', 'pointages.planning_id')
        ->select(
            'projets.designation as projet',
            'plannings.semaine',
            'plannings.jour',
            'plannings.heure_debut',
            'plannings.heure_fin',
            DB::raw("MAX(CASE WHEN pointages.motif = 'debut' THEN pointages.heure END) as debut_shift_pointe"),
            DB::raw("MAX(CASE WHEN pointages.motif = 'fin' THEN pointages.heure END) as fin_shift_pointe"),
            DB::raw("MAX(CASE WHEN pointages.motif = 'debutpause' THEN pointages.heure END) as debut_pause_pointe"),
            DB::raw("MAX(CASE WHEN pointages.motif = 'finpause' THEN pointages.heure END) as fin_pause_pointe")
        )
        ->whereNotNull('plannings.id')
        ->groupBy('projets.designation', 'plannings.semaine', 'plannings.jour', 'plannings.heure_debut', 'plannings.heure_fin')
        ->orderBy('projets.designation')
        ->orderBy('plannings.semaine')
        ->orderBy('plannings.jour')
        ->get();

    // Conversion temps
    $toTime = function($v) {
        return $v ? strtotime($v) : null;
    };

    $rapports = array();
    $globalJours = array();

    foreach ($data as $item) {
        $planningDebut = $toTime($item->heure_debut);
        $planningFin   = $toTime($item->heure_fin);
        $heureDebut    = $toTime($item->debut_shift_pointe);
        $heureFin      = $toTime($item->fin_shift_pointe);
        $pauseDebut    = $toTime($item->debut_pause_pointe);
        $pauseFin      = $toTime($item->fin_pause_pointe);

        // Heures planifiées
        $heuresPlanifiees = ($planningDebut && $planningFin)
            ? round(($planningFin - $planningDebut) / 3600, 2)
            : 0;

        // Heures de pause
        $heuresPause = ($pauseDebut && $pauseFin)
            ? round(($pauseFin - $pauseDebut) / 3600, 2)
            : 0;

        $heuresPauseDepassees = max(0, $heuresPause - 1);

        // Heures travaillées
       // Heures travaillées
        $heuresTravail = 0;
        if ($heureDebut && $heureFin) {
            $debutEffectif = max($planningDebut, min($heureDebut, $planningFin));
            $finEffectif   = max($planningDebut, min($heureFin, $planningFin));
            $heuresTravail = max(0, round(($finEffectif - $debutEffectif - $heuresPause * 3600) / 3600, 2));
            $heuresTravail = min($heuresTravail, $heuresPlanifiees);
        }

        // Filtrage : on ignore les jours sans travail
        if ($heuresTravail <= 0) {
            continue;
        }
        // Heures absentes
        $heuresAbsentes = 0;
        if ($heuresPlanifiees > 0) {
            if ($heuresTravail > 0) {
                $heuresAbsentes = $heuresPlanifiees - $heuresTravail;
            } else {
                $heuresAbsentes = $heuresPlanifiees; // correction cohérence
            }
        }

        // clé projet + semaine
        $key = $item->projet . '-' . $item->semaine;
        if (!isset($rapports[$key])) {
            $rapports[$key] = array(
                'projet' => $item->projet,
                'semaine' => $item->semaine,
                'jours' => array(),
                'total' => array(
                    'heures_planifiees' => 0,
                    'heures_travail' => 0,
                    'heures_absentes' => 0,
                    'heures_pause_depassees' => 0
                )
            );
        }

        // Agrégation par jour
        if (!isset($rapports[$key]['jours'][$item->jour])) {
            $rapports[$key]['jours'][$item->jour] = array(
                'jour' => $item->jour,
                'heures_planifiees' => 0,
                'heures_travail' => 0,
                'heures_absentes' => 0,
                'heures_pause_depassees' => 0
            );
        }

        $rapports[$key]['jours'][$item->jour]['heures_planifiees'] += $heuresPlanifiees;
        $rapports[$key]['jours'][$item->jour]['heures_travail'] += $heuresTravail;
        $rapports[$key]['jours'][$item->jour]['heures_absentes'] += $heuresAbsentes;
        $rapports[$key]['jours'][$item->jour]['heures_pause_depassees'] += $heuresPauseDepassees;

        // Totaux projet/semaine
        $rapports[$key]['total']['heures_planifiees'] += $heuresPlanifiees;
        $rapports[$key]['total']['heures_travail'] += $heuresTravail;
        $rapports[$key]['total']['heures_absentes'] += $heuresAbsentes;
        $rapports[$key]['total']['heures_pause_depassees'] += $heuresPauseDepassees;

        // Global par jour tous projets
        if (!isset($globalJours[$item->jour])) {
            $globalJours[$item->jour] = array(
                'jour' => $item->jour,
                'heures_planifiees' => 0,
                'heures_travail' => 0,
                'heures_absentes' => 0,
                'heures_pause_depassees' => 0
            );
        }

        $globalJours[$item->jour]['heures_planifiees'] += $heuresPlanifiees;
        $globalJours[$item->jour]['heures_travail'] += $heuresTravail;
        $globalJours[$item->jour]['heures_absentes'] += $heuresAbsentes;
        $globalJours[$item->jour]['heures_pause_depassees'] += $heuresPauseDepassees;
    }

    // Calcul taux d’absence par jour
    foreach ($rapports as &$rapport) {
        foreach ($rapport['jours'] as &$jour) {
            if ($jour['heures_planifiees'] > 0) {
                $jour['taux_absence'] = round(($jour['heures_absentes'] / $jour['heures_planifiees']) * 100, 2) . '%';
            } else {
                $jour['taux_absence'] = '0%';
            }
        }
        $rapport['jours'] = array_values($rapport['jours']);
    }

    foreach ($globalJours as &$gJour) {
        if ($gJour['heures_planifiees'] > 0) {
            $gJour['taux_absence'] = round(($gJour['heures_absentes'] / $gJour['heures_planifiees']) * 100, 2) . '%';
        } else {
            $gJour['taux_absence'] = '0%';
        }
    }
    $globalJours = array_values($globalJours);

    // Retirer projets sans heures planifiées
    $rapports = array_filter($rapports, function($r) {
        return $r['total']['heures_planifiees'] > 0;
    });

    return array(
        'projets' => array_values($rapports),
        'global_par_jour' => $globalJours
    );
}


public function dashboard(Request $request)
{
    // Récupérer les semaines disponibles
    $semaines = DB::table('plannings')
        ->select('semaine', DB::raw('MIN(jour) as debut'), DB::raw('MAX(jour) as fin'))
        ->groupBy('semaine')
        ->orderBy('semaine')
        ->get()
        ->map(function($s) {
            return ['numero' => $s->semaine, 'debut' => $s->debut, 'fin' => $s->fin];
        });

    $selectedWeek = $request->get('week');

    // Fonctions filtrées
    $fonctions = $request->get('fonctions', []);

    // Récupérer les rapports complets
    $rapports = $this->weeklyReport(); // fonction précédemment codée

    // Filtrer par semaine et fonctions
    $rapports = array_filter($rapports['projets'], function($r) use ($selectedWeek, $fonctions) {
        $ok = true;
        if ($selectedWeek) {
            $ok = $ok && ($r['semaine'] == $selectedWeek);
        }
        if (!empty($fonctions)) {
            // On suppose que $r a un champ 'fonction' si nécessaire
            $ok = $ok && in_array($r['fonction'] ?? '', $fonctions);
        }
        return $ok;
    });

    return view('manager.dashboard', [
        'semaines' => $semaines,
        'selectedWeek' => $selectedWeek,
        'rapports' => array_values($rapports),
        'global_par_jour' => $rapports['global_par_jour'] ?? []
    ]);
}










    public function getProjetsParSite($siteNom)
    {

    // dd($siteNom);
        // Cherche le site par nom (ou adapte si tu utilises l'ID)
        $site = Site::where('id', $siteNom)->first();

        if (!$site) {
            return response()->json([]);
        }

        // Bonne requête ici
        $projets = Projet::where('site_id', $site->id)
                        ->select('id', 'designation')
                        ->orderBy('designation')
                        ->get();

        return response()->json($projets);
    }

   public function tauxAbsence()
{
    // Récupération des agents avec leurs relations
    $agents = \DB::table('agents')
        ->leftJoin('projets', 'agents.projet_id', '=', 'projets.id')
        ->leftJoin('plannings', 'agents.id', '=', 'plannings.agent_id')
        ->leftJoin('pointages', 'plannings.id', '=', 'pointages.planning_id')
        ->select(
            'agents.id as agent_id',
            'agents.workday_id',
            'agents.nom',
            'agents.prenom',
            'agents.fonction',
            'agents.manager',
            'projets.designation as projet',
            'projets.site_id as site',
            'plannings.jour',
            'plannings.heure_debut',
            'plannings.heure_fin',
            'pointages.date',
            'pointages.heure',
            'pointages.motif'
        )
        ->get();

    $result = [];

    foreach ($agents->groupBy('agent_id') as $agentId => $rows) {
        $agent = $rows->first();

        // Heures planifiées
        $heuresPlanifiees = 0;
        foreach ($rows->groupBy('jour') as $jour => $plannings) {
            foreach ($plannings as $plan) {
                $hd = \Carbon\Carbon::parse($plan->heure_debut);
                $hf = \Carbon\Carbon::parse($plan->heure_fin);
                $heuresPlanifiees += $hd->diffInHours($hf);
            }
        }

        // Heures d'absences
        $heuresAbsence = 0;
        foreach ($rows->groupBy('jour') as $jour => $plannings) {
            foreach ($plannings as $plan) {
                if ($plan->motif === 'arrivee') {
                    $heurePlanifiee = \Carbon\Carbon::parse($plan->heure_debut);
                    $heurePointage = \Carbon\Carbon::parse($plan->heure ?? $plan->heure_debut);
                    if ($heurePointage->gt($heurePlanifiee)) {
                        $heuresAbsence += $heurePlanifiee->diffInHours($heurePointage);
                    }
                }
                if ($plan->motif === 'depart') {
                    $heureFinPlanifiee = \Carbon\Carbon::parse($plan->heure_fin);
                    $heureDepart = \Carbon\Carbon::parse($plan->heure ?? $plan->heure_fin);
                    if ($heureDepart->lt($heureFinPlanifiee)) {
                        $heuresAbsence += $heureDepart->diffInHours($heureFinPlanifiee);
                    }
                }
            }
        }

        // TA individuel
        $TA = $heuresPlanifiees > 0 ? round(($heuresAbsence / $heuresPlanifiees) * 100, 2) : 0;

        $result[] = [
            'workday_id' => $agent->workday_id,
            'nom_prenom' => $agent->nom . ' ' . $agent->prenom,
            'projet' => $agent->projet,
            'site' => $agent->site,
            'fonction' => $agent->fonction,
            'TA' => $TA,
            'nbre_jours_planifies' => $rows->groupBy('jour')->count(),
            'nbre_heures_absence' => $heuresAbsence,
            'manager' => $agent->manager,
            // Calcul global projet + site (sera recalculé plus bas)
            'TA_GLOBAL_PROJET' => null,
            'TA_GLOBAL_SITE' => null,
        ];
    }

    // Calcul TA global par projet
    $groupProjet = collect($result)->groupBy('projet');
    foreach ($result as &$row) {
        $rowsProjet = $groupProjet[$row['projet']];
        $totalAbs = collect($rowsProjet)->sum('nbre_heures_absence');
        $totalPlan = collect($rowsProjet)->sum(function ($r) {
            return $r['nbre_jours_planifies'] * 8; // on suppose 8h par jour
        });
        $row['TA_GLOBAL_PROJET'] = $totalPlan > 0 ? round(($totalAbs / $totalPlan) * 100, 2) : 0;
    }

    // Calcul TA global par site
    $groupSite = collect($result)->groupBy('site');
    foreach ($result as &$row) {
        $rowsSite = $groupSite[$row['site']];
        $totalAbs = collect($rowsSite)->sum('nbre_heures_absence');
        $totalPlan = collect($rowsSite)->sum(function ($r) {
            return $r['nbre_jours_planifies'] * 8;
        });
        $row['TA_GLOBAL_SITE'] = $totalPlan > 0 ? round(($totalAbs / $totalPlan) * 100, 2) : 0;
    }

    return response()->json($result);
}




}



