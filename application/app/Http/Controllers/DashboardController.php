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
        $site_id = $request->input('site_id');
        $projet_id = $request->input('projet_id');
        
        // Seuil métier pour les alertes (ex: 15 min)
        $setting = Setting::where('key', 'retard_minutes')->first();
        $seuilRetard = $setting ? (int)$setting->value : 15;

        // Marge technique pour éviter les faux retards (ex: 3 min pour synchro pointeuse)
        $margeTechnique = 3; 

        // 2. Récupération optimisée (Eager Loading) pour économiser la RAM
        $queryPointage = Pointage::with(['agent.projets.site', 'agent.user', 'planning'])
            ->whereBetween('date_pointage', [$debut, $fin]);

        if ($site_id) {
            $queryPointage->whereHas('agent.projets', fn($q) => $q->where('site_id', $site_id));
        }
        if ($projet_id) {
            $queryPointage->whereHas('agent.projets', fn($q) => $q->where('projets.id', $projet_id));
        }
        
        $pointages = $queryPointage->get();

        // 3. Traitement unique pour les KPIs et l'Audit Trail
        $minutesRetardGlobal = 0;
        $retardsCount = 0;
        $minutesPlanifieesGlobal = 0;

        foreach ($pointages as $p) {
            $p->is_late = false; 
            
            if ($p->planning) {
                $heurePrevue = Carbon::parse($p->planning->entree);
                $heureSortiePrevue = Carbon::parse($p->planning->sortie);
                
                // Volume planifié total
                $minutesPlanifieesGlobal += $heurePrevue->diffInMinutes($heureSortiePrevue);

                if ($p->entree) {
                    $heureArrivee = Carbon::parse($p->entree);
                    $diffMinutes = $heureArrivee->diffInMinutes($heurePrevue, false);
                    
                    // Correction : On ne flagge en retard que si > marge technique
                    if ($diffMinutes > $margeTechnique) {
                        $p->is_late = true;
                        $p->ecart_retard = $diffMinutes . ' min';
                        
                        // On comptabilise pour la "Perte" et le "Taux"
                        $minutesRetardGlobal += $diffMinutes;
                        $retardsCount++; 
                    } else {
                        $p->ecart_retard = '--'; 
                    }
                    
                    // Calcul d'écart de production (Temps travaillé vs Temps prévu)
                    $p->ecart = ($p->minutes_travaillees ?? 0) - $heurePrevue->diffInMinutes($heureSortiePrevue);
                }
            }
        }

        // 4. Statistiques par Projet (Optimisé sans requêtes N+1)
        $projetsStats = Projet::with(['site'])->get()->map(function($proj) use ($pointages, $debut, $fin) {
            $ptProj = $pointages->filter(function($pt) use ($proj) {
                return $pt->agent && $pt->agent->projets->contains($proj->id);
            });
            
            $nbRetardsSignificatifs = $ptProj->where('is_late', true)->count();
            
            // Couverture : Pointages réels / Plannings théoriques
            $totalAttendus = Planning::whereBetween('jour', [$debut, $fin])
                ->whereHas('agent.projets', fn($q) => $q->where('projets.id', $proj->id))
                ->count();

            return [
                'nom' => $proj->designation,
                'site' => $proj->site->designation ?? 'N/A',
                'taux_retard' => $ptProj->count() > 0 ? ($nbRetardsSignificatifs / $ptProj->count()) * 100 : 0,
                'taux_planification' => $totalAttendus > 0 ? ($ptProj->count() / $totalAttendus) * 100 : 0,
            ];
        });

        // 5. Données Graphiques
        $groupesDates = $pointages->groupBy(fn($p) => $p->date_pointage->format('d/m'));
        $labelsGraph = []; $dataPrevu = []; $dataRealise = [];
        
        foreach ($groupesDates as $date => $items) {
            $labelsGraph[] = $date;
            $dataPrevu[]   = $items->sum(fn($i) => $i->planning ? Carbon::parse($i->planning->entree)->diffInMinutes(Carbon::parse($i->planning->sortie)) : 0);
            $dataRealise[] = $items->sum('minutes_travaillees');
        }

        return view('dashboard.index', [
            'pointages' => $pointages,
            'projetsStats' => $projetsStats,
            'minutesRetardGlobal' => $minutesRetardGlobal,
            'minutesPlanifieesGlobal' => $minutesPlanifieesGlobal,
            'labelsGraph' => $labelsGraph,
            'dataPrevu' => $dataPrevu,
            'dataRealise' => $dataRealise,
            'ponctualite_retard' => $retardsCount,
            'ponctualite_ok' => $pointages->count() - $retardsCount,
            'sites' => Site::all(),
            'projets' => Projet::all(),
            'debut' => $debut, 'fin' => $fin, 
            'site_id' => $site_id, 'projet_id' => $projet_id,
            'semaines' => $this->generateWeekList(),
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