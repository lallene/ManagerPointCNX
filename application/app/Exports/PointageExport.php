<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\{Auth, DB};
use Carbon\Carbon;

class PointageExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $site_id, $projet_id, $week, $isFullAccess, $restrictedProjectIds;

    public function __construct($site_id, $projet_id, $week, $isFullAccess, $restrictedProjectIds)
    {
        $this->site_id = $site_id;
        $this->projet_id = $projet_id;
        $this->week = (int)$week;
        $this->isFullAccess = $isFullAccess;
        $this->restrictedProjectIds = $restrictedProjectIds;
    }

    public function collection()
    {
        $semaineCible = now()->year . '-' . str_pad($this->week, 2, '0', STR_PAD_LEFT);

        $query = DB::table('pointages')
            ->join('agents', 'pointages.agent_id', '=', 'agents.id')
            ->leftJoin('plannings', 'pointages.planning_id', '=', 'plannings.id')
            // Jointures pour remonter jusqu'au nom du projet
            ->join('agent_projet', 'agents.id', '=', 'agent_projet.agent_id')
            ->join('projets', 'agent_projet.projet_id', '=', 'projets.id')
            ->where('pointages.semaine', $semaineCible);

        // --- SÉCURITÉ PÉRIMÈTRE ---
        if (!$this->isFullAccess) {
            $query->whereIn('agent_projet.projet_id', $this->restrictedProjectIds);
        }

        // --- FILTRES OPTIONNELS ---
        if ($this->site_id) {
            $query->where('agents.site_id', $this->site_id); 
        }
        
        if ($this->projet_id && $this->projet_id !== 'null') {
            $query->where('agent_projet.projet_id', $this->projet_id);
        }

        return $query->select(
            'agents.workday_id',
            'projets.designation as projet_nom', // On récupère le nom du projet
            'agents.nom',
            'agents.prenom',
            'agents.work_email',
            'agents.fonction',
            'pointages.date_pointage',
            'plannings.entree as p_in',
            'plannings.sortie as p_out',
            'pointages.entree as a_in',
            'pointages.sortie as a_out',
            'pointages.minutes_travaillees'
        )
        ->distinct()
        ->orderBy('projets.designation') // Tri par projet pour plus de clarté
        ->orderBy('pointages.date_pointage')
        ->get()
        ->map(function($item) {
            
            // Calcul du Retard
            $retardMin = 0;
            if ($item->p_in && $item->a_in) {
                $prevu = Carbon::parse($item->p_in);
                $reel = Carbon::parse($item->a_in);
                if ($reel->gt($prevu)) {
                    $retardMin = $reel->diffInMinutes($prevu);
                }
            }

            return [
                'Workday ID'        => $item->workday_id,
                'Projet'            => $item->projet_nom, // Nouvelle colonne
                'Agent'             => strtoupper($item->nom) . ' ' . $item->prenom,
                'Email'             => $item->work_email,
                'Fonction'          => $item->fonction ?? 'N/A',
                'Date'              => Carbon::parse($item->date_pointage)->format('d/m/Y'),
                'Prévu IN'          => $item->p_in ? Carbon::parse($item->p_in)->format('H:i') : '-',
                'Prévu OUT'         => $item->p_out ? Carbon::parse($item->p_out)->format('H:i') : '-',
                'Réel IN'           => $item->a_in ? Carbon::parse($item->a_in)->format('H:i') : '-',
                'Réel OUT'          => $item->a_out ? Carbon::parse($item->a_out)->format('H:i') : '-',
                'Temps Travail'     => $this->formatMinutes($item->minutes_travaillees),
                'Retard'            => $this->formatMinutes($retardMin),
            ];
        });
    }

    private function formatMinutes($totalMinutes)
    {
        if (!$totalMinutes || $totalMinutes <= 0) return "00:00";
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function headings(): array
    {
        return [
            'Workday ID',
            'Projet', // Nouveau Header
            'Agent',
            'Email',
            'Fonction',
            'Date',
            'Prévu IN',
            'Prévu OUT',
            'Réel IN',
            'Réel OUT',
            'Temps Travail',
            'Retard'
        ];
    }
}