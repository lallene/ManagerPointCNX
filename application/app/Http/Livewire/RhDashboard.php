<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Pointage;
use App\Models\Site;
use App\Models\Projet;
use App\Models\Agent;

class RhDashboard extends Component
{
    public $mois;
    public $site;
    public $projet;
    public $agent;

    public $sites;
    public $projets;
    public $agents;

    public function mount()
    {
        $this->mois = now()->format('Y-m'); // mois courant
        $this->sites = Site::all();
        $this->projets = Projet::all();
        $this->agents = Agent::all();
    }

    public function render()
    {
        $query = Pointage::with('user.agent.projet.site');

        // Filtrer par mois
        if ($this->mois) {
            $query->whereMonth('created_at', date('m', strtotime($this->mois)))
                  ->whereYear('created_at', date('Y', strtotime($this->mois)));
        }

        // Filtrer par site
        if ($this->site) {
            $query->whereHas('user.agent.projet.site', function($q) {
                $q->where('id', $this->site);
            });
        }

        // Filtrer par projet
        if ($this->projet) {
            $query->whereHas('user.agent.projet', function($q) {
                $q->where('id', $this->projet);
            });
        }

        // Filtrer par agent
        if ($this->agent) {
            $query->whereHas('user.agent', function($q) {
                $q->where('id', $this->agent);
            });
        }

        $pointages = $query->get(); // ✅ juste get() pour l’instant

        return view('livewire.rh-dashboard', compact('pointages'));
    }
}
