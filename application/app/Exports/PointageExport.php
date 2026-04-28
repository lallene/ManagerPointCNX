<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PointageExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    // Propriétés de filtrage
    protected $site_id, $projet_id, $dateDebut, $dateFin, $isFullAccess, $restrictedProjectIds;
    
    // Stockage des index de lignes pour le formatage conditionnel (AfterSheet)
    private $rowsWithAlert = []; 

    public function __construct($site_id, $projet_id, $dateDebut, $dateFin, $isFullAccess, $restrictedProjectIds)
    {
        $this->site_id = $site_id;
        $this->projet_id = $projet_id;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->isFullAccess = $isFullAccess;
        $this->restrictedProjectIds = $restrictedProjectIds;
    }

    /**
     * Récupération et traitement des données
     */
    
    public function collection()
{
    // Initialisation de la requête
    $query = DB::table('plannings')
        ->join('agents', 'plannings.agent_id', '=', 'agents.id')
        ->join('agent_projet', 'agents.id', '=', 'agent_projet.agent_id')
        ->join('projets', 'agent_projet.projet_id', '=', 'projets.id')
        ->leftJoin('pointages', function($join) {
            $join->on('plannings.id', '=', 'pointages.planning_id');
        })
        ->whereBetween('plannings.jour', [$this->dateDebut, $this->dateFin]) // Point-virgule supprimé ici
        ->where(function($q) {
            $q->where('agents.fonction', 'LIKE', 'SUPERVISEUR%')
              ->orWhere('agents.fonction', 'LIKE', 'CONTRÔLEUR%')
              ->orWhere('agents.fonction', 'LIKE', 'FORMATEUR%');
        });

    // --- Filtres de sécurité et de périmètre ---
    if (!$this->isFullAccess) {
        $query->whereIn('agent_projet.projet_id', $this->restrictedProjectIds);
    }

    if ($this->site_id) {
        $query->where('projets.site_id', $this->site_id);
    }

    if ($this->projet_id && $this->projet_id !== 'null' && $this->projet_id !== '') {
        $query->where('agent_projet.projet_id', $this->projet_id);
    }

    // Sélection et tri
    $data = $query->select(
        'agents.workday_id',
        'projets.designation as projet_nom',
        'agents.nom',
        'agents.prenom',
        'agents.work_email',
        'agents.fonction',
        'plannings.jour as date_ref',
        'plannings.entree as p_in',
        'plannings.sortie as p_out',
        'pointages.entree as a_in',
        'pointages.sortie as a_out',
        'pointages.pause_debut',
        'pointages.pause_fin',
        'pointages.minutes_travaillees'
    )
    ->distinct()
    ->orderBy('plannings.jour', 'asc')
    ->orderBy('projets.designation', 'asc')
    ->get();

    // Transformation des données pour l'export
    return $data->map(function ($item, $key) {
        
        // 1. Calcul du temps de travail (Fallback si minutes_travaillees est null)
        $travailReel = (int)$item->minutes_travaillees;
        
        if ($travailReel <= 0 && $item->a_in && $item->a_out) {
            $debut = Carbon::parse($item->a_in);
            $fin = Carbon::parse($item->a_out);
            $travailReel = $debut->diffInMinutes($fin);
            
            if ($item->pause_debut && $item->pause_fin) {
                $pause = Carbon::parse($item->pause_debut)->diffInMinutes(Carbon::parse($item->pause_fin));
                $travailReel = max(0, $travailReel - $pause);
            }
        }

        $objectifTravail = 480; 
        $isAbsent = (empty($item->a_in) && empty($item->a_out));
        
        // 2. Logique de Retard / Déficit
        $deficitTravail = ($travailReel < $objectifTravail) ? ($objectifTravail - $travailReel) : 0;
        $retardMinutes = ($isAbsent) ? $objectifTravail : (($deficitTravail >= 5) ? $deficitTravail : 0);

        // 3. Logique de Pause
        $minutesPause = 0;
        $alertePause = false;
        if ($item->pause_debut && $item->pause_fin) {
            $minutesPause = Carbon::parse($item->pause_debut)->diffInMinutes(Carbon::parse($item->pause_fin));
            $alertePause = ($minutesPause > 70);
        }

        // Identification des lignes à mettre en rouge
        if ($isAbsent || $retardMinutes > 0 || $alertePause) {
            $this->rowsWithAlert[] = $key + 2;
        }

        return [
            'Workday ID'    => $item->workday_id,
            'Projet'        => $item->projet_nom,
            'Agent'         => strtoupper($item->nom) . ' ' . $item->prenom,
            'Email'         => $item->work_email,
            'Fonction'      => $item->fonction ?? 'N/A',
            'Date'          => Carbon::parse($item->date_ref)->format('d/m/Y'),
            'Prévu IN'      => $item->p_in ? Carbon::parse($item->p_in)->format('H:i') : '-',
            'Prévu OUT'     => $item->p_out ? Carbon::parse($item->p_out)->format('H:i') : '-',
            'Réel IN'       => $item->a_in ? Carbon::parse($item->a_in)->format('H:i') : ($isAbsent ? 'ABSENT' : '-'),
            'Réel OUT'      => $item->a_out ? Carbon::parse($item->a_out)->format('H:i') : ($isAbsent ? 'ABSENT' : '-'),
            'Temps Travail' => $isAbsent ? '00:00' : $this->formatMinutes($travailReel),
            'Pause'         => $this->formatMinutes($minutesPause),
            'Retard'        => $this->formatMinutes($retardMinutes)
        ];
    });
}

    /**
     * Formatage stylisé de l'Excel
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Figer l'entête
                $sheet->freezePane('A2');

                // Style de l'entête
                $headerRange = 'A1:M1';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F2F2F2');

                // Application des alertes visuelles (Rouge + Gras)
                foreach ($this->rowsWithAlert as $row) {
                    $range = "A{$row}:M{$row}";
                    $sheet->getStyle($range)->getFont()->setBold(true);
                    $sheet->getStyle($range)->getFont()->getColor()->setARGB('FFFF0000');
                }
            },
        ];
    }

    /**
     * Helper pour formater les minutes en HH:mm
     */
    private function formatMinutes($totalMinutes)
    {
        if ($totalMinutes <= 0) return "00:00";
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * En-têtes des colonnes
     */
    public function headings(): array
    {
        return [
            'Workday ID', 'Projet', 'Agent', 'Email', 'Fonction', 'Date',
            'Prévu IN', 'Prévu OUT', 'Réel IN', 'Réel OUT', 
            'Temps Travail', 'Pause', 'Retard'
        ];
    }
}