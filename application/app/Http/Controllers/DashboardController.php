<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Pointage, Planning, Site, Projet, Setting, Agent};
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller        
{

    public function index(Request $request): View
{
    // 1. Initialisation des Filtres
    $debut = $request->input('debut', now()->startOfWeek()->format('Y-m-d'));
    $fin   = $request->input('fin', now()->endOfWeek()->format('Y-m-d'));
   $site_id = $request->input('site_id'); // <--- IL MANQUAIT CETTE LIGNE
    $projet_id = $request->input('projet_id');

    // 2. Récupération des données pour les filtres
    $sites = Site::all();

    // On filtre les projets dynamiquement si un site est sélectionné
    $projets = Projet::when($site_id, function($query) use ($site_id) {
        return $query->where('site_id', $site_id);
    })->get();

    // Maintenant le dd() fonctionnera parfaitement
    //dd($sites, $projets);
    
    $setting = Setting::where('key', 'retard_minutes')->first();
    $seuilRetard = $setting ? (int)$setting->value : 15;
    $margeTechnique = 3; 

    // 2. Récupération optimisée
    $queryPointage = Pointage::with(['agent.projets.site', 'agent.user', 'planning'])
        ->whereBetween('date_pointage', [$debut, $fin]);

    if ($site_id) {
        $queryPointage->whereHas('agent.projets', fn($q) => $q->where('site_id', $site_id));
    }
    if ($projet_id) {
        $queryPointage->whereHas('agent.projets', fn($q) => $q->where('projets.id', $projet_id));
    }
    
    $pointages = $queryPointage->get();

    // 3. Traitement des KPIs
    $minutesRetardGlobal = 0;
    $retardsCount = 0;
    $minutesPlanifieesGlobal = 0;

    foreach ($pointages as $p) {
        $p->is_late = false; 
        if ($p->planning) {
            $heurePrevue = Carbon::parse($p->planning->entree);
            $heureSortiePrevue = Carbon::parse($p->planning->sortie);
            $minutesPlanifieesGlobal += $heurePrevue->diffInMinutes($heureSortiePrevue);

            if ($p->entree) {
                $heureArrivee = Carbon::parse($p->entree);
                $diffMinutes = $heureArrivee->diffInMinutes($heurePrevue, false);
                
                if ($diffMinutes > $margeTechnique) {
                    $p->is_late = true;
                    $p->ecart_retard = $diffMinutes . ' min';
                    $minutesRetardGlobal += $diffMinutes;
                    $retardsCount++; 
                } else {
                    $p->ecart_retard = '--'; 
                }
                $p->ecart = ($p->minutes_travaillees ?? 0) - $heurePrevue->diffInMinutes($heureSortiePrevue);
            }
        }
    }

    // 4. Statistiques par Projet
    $projetsStats = Projet::with(['site'])->get()->map(function($proj) use ($pointages, $debut, $fin) {
        $ptProj = $pointages->filter(fn($pt) => $pt->agent && $pt->agent->projets->contains($proj->id));
        $nbRetards = $ptProj->where('is_late', true)->count();
        $totalAttendus = Planning::whereBetween('jour', [$debut, $fin])
            ->whereHas('agent.projets', fn($q) => $q->where('projets.id', $proj->id))->count();

        return [
            'nom' => $proj->designation,
            'site' => $proj->site->designation ?? 'N/A',
            'taux_retard' => $ptProj->count() > 0 ? ($nbRetards / $ptProj->count()) * 100 : 0,
            'taux_planification' => $totalAttendus > 0 ? ($ptProj->count() / $totalAttendus) * 100 : 0,
        ];
    });

    // 5. Analyse de l'Adhérence & Graphiques
    $totalMinutesRealisees = $pointages->sum('minutes_travaillees');
    $tauxAdherenceGlobal = $minutesPlanifieesGlobal > 0 ? ($totalMinutesRealisees / $minutesPlanifieesGlobal) * 100 : 0;

    $retardsParJour = [
        'Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 
        'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0
    ];

    foreach ($pointages->where('is_late', true) as $p) {
        // format('l') renvoie toujours le jour en anglais (ex: "Monday")
        $nomJour = Carbon::parse($p->date_pointage)->format('l');
        
        if (isset($retardsParJour[$nomJour])) {
            $retardsParJour[$nomJour]++;
        }
    }
    $topRetardataires = $pointages->where('is_late', true)->groupBy('agent_id')->map(function($pts) {
        return [
            'nom' => $pts->first()->agent->user->name ?? 'Inconnu',
            'total_retard' => $pts->sum(fn($i) => (int)filter_var($i->ecart_retard, FILTER_SANITIZE_NUMBER_INT)),
            'count' => $pts->count()
        ];
    })->sortByDesc('total_retard')->take(5);

    $groupesDates = $pointages->groupBy(fn($p) => $p->date_pointage->format('d/m'));
    $labelsGraph = []; $dataPrevu = []; $dataRealise = [];
    foreach ($groupesDates as $date => $items) {
        $labelsGraph[] = $date;
        $dataPrevu[] = $items->sum(fn($i) => $i->planning ? Carbon::parse($i->planning->entree)->diffInMinutes(Carbon::parse($i->planning->sortie)) : 0);
        $dataRealise[] = $items->sum('minutes_travaillees');
    }

    // Optionnel : Traduire les clés en français juste avant l'envoi à la vue
    $retardsTraduits = [
        'Lundi'    => $retardsParJour['Monday'],
        'Mardi'    => $retardsParJour['Tuesday'],
        'Mercredi' => $retardsParJour['Wednesday'],
        'Jeudi'    => $retardsParJour['Thursday'],
        'Vendredi' => $retardsParJour['Friday'],
        'Samedi'   => $retardsParJour['Saturday'],
        'Dimanche' => $retardsParJour['Sunday'],
    ];

    // UN SEUL RETURN avec toutes les variables
    return view('dashboard.index', [
        'pointages' => $pointages,
        'projetsStats' => $projetsStats,
        'minutesRetardGlobal' => $minutesRetardGlobal,
        'minutesPlanifieesGlobal' => $minutesPlanifieesGlobal,
        'labelsGraph' => $labelsGraph,
        'dataPrevu' => $dataPrevu,
        'dataRealise' => $dataRealise,
        'tauxAdherenceGlobal' => $tauxAdherenceGlobal,
        'retardsParJour' => $retardsParJour,
        'topRetardataires' => $topRetardataires,
        'ponctualite_retard' => $retardsCount,
        'ponctualite_ok' => $pointages->count() - $retardsCount,
        'sites' => $sites,
        'projets' => $projets,
        'debut' => $debut, 'fin' => $fin, 
        'site_id' => $site_id, 'projet_id' => $projet_id,
        'semaines' => $this->generateWeekList(),
        'retardsParJour' => $retardsTraduits,
    ]);
}
    private function generateWeekList(): array {
        $weeks = [];
        for ($i = -1; $i <= 3; $i++) {
            $d = now()->addWeeks($i);
            $weeks[] = [
                'numero' => $d->weekOfYear,
                'debut' => $d->startOfWeek()->format('d/m'),
                'fin' => $d->endOfWeek()->format('d/m')
            ];
        }
        return $weeks;
    }
}