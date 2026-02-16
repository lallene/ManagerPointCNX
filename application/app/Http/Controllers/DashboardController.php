<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Pointage, Planning, Site, Projet, Setting, Agent};
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard Global : Saisie, Performance et Audit
     */
    public function index(Request $request)
    {
        // 1. Initialisation des Filtres
        $debut = $request->input('debut', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $fin   = $request->input('fin', Carbon::now()->endOfWeek()->format('Y-m-d'));
        $site_id = $request->input('site_id');
        $projet_id = $request->input('projet_id');
        
        // Seuil de tolérance (minutes) pour les retards
        $seuilRetard = (int)optional(Setting::where('key', 'retard_minutes')->first())->value ?: 15;

        // 2. Mapping des fonctions pour l'encadrement
        $fonctionMapping = [
            'Superviseur' => ['Superviseur', 'Team Leader Trainee, Operations', 'Team Leader, Operations', 'SUPERVISEUR'],
            'Formateur' => ['Formateur', 'Trainer', 'FORMATEUR METIER', 'Trainer I - Trainee', 'Trainer II'],
            'CQ' => ['CQ', 'Quality Analyst', 'Contrôle qualité', 'Quality Evaluator - Trainee (Agent)', 'Contrôleur Qualité (mission)', 'CONTROLEUR QUALITE', 'Sr. Quality Evaluator', 'CONTROLEUR QUALITE PRODUCTION', 'Contrôleur Qualité Mission', 'Quality Lead'],
        ];

        // 3. Récupération des Pointages (Audit Trail)
        $queryPointage = Pointage::with(['agent.projet.site', 'agent.user', 'planning'])
            ->whereBetween('date_pointage', [$debut, $fin]);

        if ($site_id) {
            $queryPointage->whereHas('agent.projet', function($q) use ($site_id) { $q->where('site_id', $site_id); });
        }
        if ($projet_id) {
            $queryPointage->whereHas('agent', function($q) use ($projet_id) { $q->where('projet_id', $projet_id); });
        }
        $pointages = $queryPointage->get();

        // 4. Calculs des Volumes Horaires et Retards
        $minutesRetardGlobal = 0;
        $retardsCount = 0;
        
        $allPlannings = Planning::whereBetween('jour', [$debut, $fin])->get();
        $minutesPlanifieesGlobal = $allPlannings->sum(function($pl) {
            return Carbon::parse($pl->entree)->diffInMinutes(Carbon::parse($pl->sortie));
        });

        foreach ($pointages as $p) {
            $p->is_late = false;
            if ($p->entree && $p->planning) {
                $heurePrevue = Carbon::parse($p->planning->entree);
                $heureArrivee = Carbon::parse($p->entree);
                
                if ($heureArrivee->gt($heurePrevue->copy()->addMinutes($seuilRetard))) {
                    $p->is_late = true;
                    $retardsCount++;
                    // Calcul de l'écart pour la vue
                    $p->ecart_retard = $heureArrivee->diffInMinutes($heurePrevue) . ' min';
                    $minutesRetardGlobal += $heureArrivee->diffInMinutes($heurePrevue);
                }
                
                $prevu = Carbon::parse($p->planning->entree)->diffInMinutes(Carbon::parse($p->planning->sortie));
                $p->ecart = ($p->minutes_travaillees ?? 0) - $prevu;
            }
        }

        // 5. Statistiques de Santé par Projet
        $projetsStats = Projet::with(['agents', 'site'])->get()->map(function($proj) use ($pointages, $debut, $fin) {
            $ptProj = $pointages->filter(function($pt) use ($proj) { return $pt->agent->projet_id == $proj->id; });
            $nbRetards = $ptProj->where('is_late', true)->count();
            
            $agentsPlanifies = Planning::whereBetween('jour', [$debut, $fin])
                ->whereHas('agent', function($q) use ($proj) { $q->where('projet_id', $proj->id); })
                ->distinct('agent_id')->count();

            return [
                'nom' => $proj->designation,
                'site' => $proj->site->designation ?? 'N/A',
                'taux_retard' => $ptProj->count() > 0 ? ($nbRetards / $ptProj->count()) * 100 : 0,
                'taux_planification' => $proj->agents->count() > 0 ? ($agentsPlanifies / $proj->agents->count()) * 100 : 0,
            ];
        });

        // 6. Analyse de l'Encadrement (Managers)
        $managersParFonction = [];
        foreach ($fonctionMapping as $label => $variantes) {
            $managersParFonction[$label] = Planning::whereBetween('jour', [$debut, $fin])
                ->whereHas('agent', function($q) use ($variantes) { $q->whereIn('fonction', $variantes); })
                ->distinct('agent_id')->count();
        }

        // 7. Données pour la Saisie des Plannings (Correction de l'erreur)
        $agentsQuery = Agent::with(['user', 'projet']);
        if ($projet_id) { $agentsQuery->where('projet_id', $projet_id); }
        $agents = $agentsQuery->get();

        $planningsSaisie = Planning::whereBetween('jour', [$debut, $fin])
            ->get()
            ->keyBy(fn($item) => $item->agent_id . '-' . $item->jour);

        $categoriesDispo = Agent::distinct()->pluck('fonction')->toArray();
        $selectedWeekNum = Carbon::parse($debut)->weekOfYear;

        // 8. Données Graphique
        $groupesDates = $pointages->groupBy(fn($p) => Carbon::parse($p->date_pointage)->format('d/m'));
        $labelsGraph = []; $dataPrevu = []; $dataRealise = [];
        foreach ($groupesDates as $date => $items) {
            $labelsGraph[] = $date;
            $dataPrevu[]   = $items->sum(fn($i) => $i->planning ? Carbon::parse($i->planning->entree)->diffInMinutes(Carbon::parse($i->planning->sortie)) : 0);
            $dataRealise[] = $items->sum('minutes_travaillees');
        }


        // 10. Statistiques Projets par Sites
        $projetsParSite = Site::withCount('projets')->get();



        // 9. Retour de la vue avec TOUTES les variables
        return view('dashboard.index', [
            'pointages' => $pointages,
            'projetsStats' => $projetsStats,
            'managersParFonction' => $managersParFonction,
            'minutesRetardGlobal' => $minutesRetardGlobal,
            'minutesPlanifieesGlobal' => $minutesPlanifieesGlobal,
            'labelsGraph' => $labelsGraph,
            'dataPrevu' => $dataPrevu,
            'dataRealise' => $dataRealise,
            'ponctualite_retard' => $retardsCount,
            'ponctualite_ok' => $pointages->count() - $retardsCount,
            'sites' => Site::all(),
            'projets' => Projet::all(),
            'debut' => $debut, 'fin' => $fin, 'site_id' => $site_id, 'projet_id' => $projet_id,
            'agents' => $agents,
            'plannings' => $planningsSaisie,
            'selectedWeekNum' => $selectedWeekNum,
            'categoriesDispo' => $categoriesDispo,
            'semaines' => $this->generateWeekList(),
            'projetsParSite' => $projetsParSite,
            'fonctionsChoisies' => (array)$request->input('fonctions', $categoriesDispo)
        ]);
    }

    private function generateWeekList() {
        $weeks = [];
        for ($i = -1; $i <= 3; $i++) {
            $d = Carbon::now()->addWeeks($i);
            $weeks[] = [
                'numero' => $d->weekOfYear,
                'debut' => $d->startOfWeek()->format('d/m'),
                'fin' => $d->endOfWeek()->format('d/m')
            ];
        }
        return $weeks;
    }
}