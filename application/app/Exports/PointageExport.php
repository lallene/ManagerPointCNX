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
    protected $site_id, $projet_id, $week, $isFullAccess, $restrictedProjectIds;
    private $rowsWithAlert = []; // Stocke les lignes nécessitant une alerte visuelle (Index Excel)

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
        // Formatage de la semaine pour correspondre à la BDD (ex: 2026-11)
        $semaineCible = now()->year . '-' . str_pad($this->week, 2, '0', STR_PAD_LEFT);

        $query = DB::table('pointages')
            ->join('agents', 'pointages.agent_id', '=', 'agents.id')
            ->leftJoin('plannings', 'pointages.planning_id', '=', 'plannings.id')
            ->join('agent_projet', 'agents.id', '=', 'agent_projet.agent_id')
            ->join('projets', 'agent_projet.projet_id', '=', 'projets.id')
            ->where('pointages.semaine', $semaineCible);

        if (!$this->isFullAccess) {
            $query->whereIn('agent_projet.projet_id', $this->restrictedProjectIds);
        }

        if ($this->site_id) {
            $query->where('agents.site_id', $this->site_id);
        }

        if ($this->projet_id && $this->projet_id !== 'null') {
            $query->where('agent_projet.projet_id', $this->projet_id);
        }

        $data = $query->select(
            'agents.workday_id',
            'projets.designation as projet_nom',
            'agents.nom',
            'agents.prenom',
            'agents.work_email',
            'agents.fonction',
            'pointages.date_pointage',
            'plannings.entree as p_in',
            'plannings.sortie as p_out',
            'pointages.entree as a_in',
            'pointages.sortie as a_out',
            'pointages.pause_debut',
            'pointages.pause_fin',
            'pointages.minutes_travaillees'
        )
        ->distinct()
        ->orderBy('projets.designation')
        ->orderBy('pointages.date_pointage')
        ->get();

        return $data->map(function ($item, $key) {
            $objectifTravail = 480; // 8 heures effectives
            $travailReel = (int)$item->minutes_travaillees;
            
            // 1. Logique Retard (Alerte si déficit >= 5 minutes)
            $deficitTravail = ($travailReel < $objectifTravail) ? ($objectifTravail - $travailReel) : 0;
            $retardMinutes = ($deficitTravail >= 5) ? $deficitTravail : 0;

            // 2. Logique Pause (1h autorisée, alerte si > 1h10 / 70 min)
            $minutesPause = 0;
            $alertePause = false;
            if ($item->pause_debut && $item->pause_fin) {
                $minutesPause = Carbon::parse($item->pause_debut)->diffInMinutes(Carbon::parse($item->pause_fin));
                if ($minutesPause > 70) {
                    $alertePause = true;
                }
            }

            // Si retard significatif OU pause trop longue, on marque la ligne pour AfterSheet
            if ($retardMinutes > 0 || $alertePause) {
                // Index Excel = Index Collection (0-based) + 2 (Header + Excel 1-based)
                $this->rowsWithAlert[] = $key + 2;
            }

            return [
                'Workday ID'    => $item->workday_id,
                'Projet'        => $item->projet_nom,
                'Agent'         => strtoupper($item->nom) . ' ' . $item->prenom,
                'Email'         => $item->work_email,
                'Fonction'      => $item->fonction ?? 'N/A',
                'Date'          => Carbon::parse($item->date_pointage)->format('d/m/Y'),
                'Prévu IN'      => $item->p_in ? Carbon::parse($item->p_in)->format('H:i') : '-',
                'Prévu OUT'     => $item->p_out ? Carbon::parse($item->p_out)->format('H:i') : '-',
                'Réel IN'       => $item->a_in ? Carbon::parse($item->a_in)->format('H:i') : '-',
                'Réel OUT'      => $item->a_out ? Carbon::parse($item->a_out)->format('H:i') : '-',
                'Temps Travail' => $this->formatMinutes($travailReel),
                'Pause'         => $this->formatMinutes($minutesPause),
                'Retard'        => $this->formatMinutes($retardMinutes)
            ];
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Figer la première ligne
                $sheet->freezePane('A2');

                // Style de l'entête (Gras + Fond gris)
                $sheet->getStyle('A1:M1')->getFont()->setBold(true);
                $sheet->getStyle('A1:M1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F2F2F2');

                // Application du style visuel pour les alertes (Rouge et Gras)
                foreach ($this->rowsWithAlert as $row) {
                    $range = "A{$row}:M{$row}";
                    $sheet->getStyle($range)->getFont()->setBold(true);
                    $sheet->getStyle($range)->getFont()->getColor()->setARGB('FFFF0000');
                }
            },
        ];
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
            'Projet',
            'Agent',
            'Email',
            'Fonction',
            'Date',
            'Prévu IN',
            'Prévu OUT',
            'Réel IN',
            'Réel OUT',
            'Temps Travail',
            'Pause',
            'Retard'
        ];
    }
}