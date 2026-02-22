<?php

namespace App\Exports;

use App\Models\Pointage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointageExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $site_id, $projet_id, $week;

    public function __construct($site_id, $projet_id, $week)
    {
        $this->site_id = $site_id;
        $this->projet_id = $projet_id;
        $this->week = $week;
    }

    public function collection()
    {
        $user = Auth::user();
        
        // 1. DÉTERMINATION DYNAMIQUE DE LA SEMAINE
        $currentYear = date('Y'); 
        $currentWeek = (int)date('W');
        $annee = ($this->week > $currentWeek + 10) ? ($currentYear - 1) : $currentYear;
        $semaineFormatee = $annee . '-' . str_pad($this->week, 2, '0', STR_PAD_LEFT); 

        // 2. LOGIQUE D'ACCÈS
        $isFullAccess = ($user->work_email === 'admin@concentrix.com') || 
                        $user->hasAnyRole(['IT', 'RH', 'Directeur']);

        // 3. REQUÊTE AVEC JOINTURES
        $query = DB::table('pointages')
            ->join('agents', 'pointages.agent_id', '=', 'agents.id')
            ->leftJoin('plannings', 'pointages.planning_id', '=', 'plannings.id')
            ->where('pointages.semaine', $semaineFormatee);

        // 4. FILTRAGES (Site & Projet)
        if ($this->site_id) $query->where('pointages.site_id', $this->site_id);

        if (!$isFullAccess) {
            $userProjectIds = $user->projets()->pluck('projets.id')->toArray();
            if ($this->projet_id && in_array($this->projet_id, $userProjectIds)) {
                $query->where('pointages.projet_id', $this->projet_id);
            } else {
                $query->whereIn('pointages.projet_id', $userProjectIds);
            }
        } elseif ($this->projet_id) {
            $query->where('pointages.projet_id', $this->projet_id);
        }

        // 5. TRAITEMENT ET CALCULS
        return $query->select(
            'agents.workday_id',
            'agents.work_email',
            'agents.fonction',
            'pointages.date_pointage',
            'agents.nom', 
            'agents.prenom', 
            'plannings.entree as planning_entree', 
            'plannings.sortie as planning_sortie', 
            'pointages.entree as reel_entree', 
            'pointages.sortie as reel_sortie',
            'pointages.minutes_travaillees'
        )->get()->map(function($item) {
            
            // Calcul du retard (Fiable via strtotime)
            $retardMinutes = 0;
            if ($item->planning_entree && $item->reel_entree) {
                $h_prevu = strtotime($item->planning_entree);
                $h_reel = strtotime($item->reel_entree);
                
                if ($h_reel > $h_prevu) {
                    $retardMinutes = ($h_reel - $h_prevu) / 60;
                }
            }

            return [
                'Workday ID' => $item->workday_id ,
                'Agent' => strtoupper($item->nom) . ' ' . $item->prenom, 
                'email' => $item->work_email ,
                'Fonction' => $item->fonction,
                'Date' => $item->planning_entree,
                'Prévu IN' => $item->planning_entree,
                'Prévu OUT' => $item->planning_sortie,
                'Réel IN' => $item->reel_entree,
                'Réel OUT' => $item->reel_sortie,
                'Temps Travail' => $this->formatMinutes($item->minutes_travaillees),
                'Retard' => $this->formatMinutes($retardMinutes),
            ];
        });
    }

    /**
     * Helper de formatage HH:MM
     */
    private function formatMinutes($totalMinutes)
    {
        $totalMinutes = round($totalMinutes);
        if ($totalMinutes <= 0) return "00:00";
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function headings(): array
    {
        return [
            'Workday ID',
            "Agent", 
            'Email',
            'Fonction',
            'Date de pointage',
            "Prévu Entrée", 
            "Prévu Sortie", 
            "Réel Entrée", 
            "Réel Sortie", 
            "Temps Travail (HH:MM)", 
            "Retard (HH:MM)"
        ];
    }
}