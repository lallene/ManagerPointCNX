<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\{Pointage, Planning, Site, Projet, Setting};
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $site_id;
    public $projet_id;
    public $debut;
    public $fin;
    public $seuilRetard;

    public function mount()
    {
        $this->debut = now()->startOfWeek()->format('Y-m-d');
        $this->fin   = now()->endOfWeek()->format('Y-m-d');
        $this->seuilRetard = Setting::where('key', 'retard_minutes')->value('value') ?? 5;
    }

    public function getFilteredQueryProperty()
    {
        return Pointage::with(['user', 'planning', 'agent.projet.site'])
            ->whereBetween('date_pointage', [$this->debut, $this->fin])
            // Filtre par PROJET (via Agent)
            ->when($this->projet_id, function($q) {
                $q->whereHas('agent', function($query) {
                    $query->where('projet_id', $this->projet_id);
                });
            })
            // Filtre par SITE (via Agent -> Projet)
            ->when($this->site_id, function($q) {
                $q->whereHas('agent.projet', function($query) {
                    $query->where('site_id', $this->site_id);
                });
            });
    }

    public function render()
    {
        $pointagesRaw = $this->filtered_query->get();
        $retardsCount = 0;

        $pointages = $pointagesRaw->map(function ($p) use (&$retardsCount) {
            $p->retard = false;
            
            // On vérifie l'heure d'entrée par rapport au planning
            if ($p->entree && $p->planning?->heure_debut) {
                $isLate = Carbon::parse($p->entree)
                    ->gt(Carbon::parse($p->planning->heure_debut)->addMinutes((int)$this->seuilRetard));
                
                $p->retard = $isLate;
                if ($isLate) $retardsCount++;
            }
            return $p;
        });

        // Calcul du taux de couverture avec la même logique de filtres imbriqués
        $planifiesCount = Planning::whereBetween('jour', [$this->debut, $this->fin])
            ->when($this->projet_id, fn($q) => $q->whereHas('agent', fn($sq) => $sq->where('projet_id', $this->projet_id)))
            ->when($this->site_id, fn($q) => $q->whereHas('agent.projet', fn($sq) => $sq->where('site_id', $this->site_id)))
            ->count();

        $tauxCouverture = $planifiesCount > 0 
            ? round(($pointages->whereNotNull('entree')->count() / $planifiesCount) * 100, 1)
            : 0;

        return view('livewire.dashboard', [
            'pointages'      => $pointages,
            'tauxCouverture' => $tauxCouverture,
            'retards'        => $retardsCount,
            'top5'           => $this->getTop5(),
            'sites'          => Site::all(['id', 'designation']),
            'projets'        => Projet::all(['id', 'designation']),
        ]);
    }

    private function getTop5()
    {
        return Pointage::query()
            ->select('agent_id', DB::raw('SUM(minutes_travaillees) as total'))
            ->whereBetween('date_pointage', [$this->debut, $this->fin])
            ->groupBy('agent_id')
            ->orderByDesc('total')
            ->with('agent:id,nom') 
            ->take(5)
            ->get();
    }
}