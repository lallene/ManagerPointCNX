<?php

namespace App\Imports;

use App\Models\Planning;
use App\Models\Agent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PlanningsImport implements ToCollection, WithHeadingRow
{
    private $week;

    public function __construct($week)
    {
        // On s'assure que si on reçoit "8", on stocke "2026-08"
        // (Adaptation dynamique selon l'année en cours)
        $this->week = str_contains($week, '-') ? $week : now()->year . '-' . str_pad($week, 2, '0', STR_PAD_LEFT);
    }

    public function collection(Collection $rows)
    {
        $authUserId = Auth::id();
        $today = now()->startOfDay();

        $topManager = Agent::with('projets')->where('work_email', Auth::user()->work_email)->first();
        if (!$topManager) return;
        
        $allowedProjetIds = $topManager->projets->pluck('id')->toArray();

        foreach ($rows as $row) {
            if (empty($row['workday_id']) || empty($row['date'])) continue;

            $targetAgent = Agent::with('projets')->where('workday_id', $row['workday_id'])->first();
            if (!$targetAgent) continue;

            $targetAgentProjets = $targetAgent->projets->pluck('id')->toArray();
            if (empty(array_intersect($allowedProjetIds, $targetAgentProjets))) continue;

            try {
                if (is_numeric($row['date'])) {
                    $datePlanning = Carbon::instance(ExcelDate::excelToDateTimeObject($row['date']));
                } else {
                    $datePlanning = Carbon::createFromFormat('d/m/Y', $row['date']);
                }
            } catch (\Exception $e) {
                continue; 
            }

            if ($datePlanning->startOfDay()->isBefore($today)) {
                continue; 
            }
        

            // Calcul de la semaine ISO basée sur la date réelle du fichier pour éviter les décalages
            $semaineISO = $datePlanning->format('o-W'); 
                //        dd($targetAgent->id, $datePlanning->format('Y-m-d'),$this->formatTime($row['entree']), $this->formatTime($row['sortie']), $semaineISO, $authUserId);


            Planning::updateOrCreate(
                [
                    'agent_id' => $targetAgent->id,
                    'jour'     => $datePlanning->format('Y-m-d'),
                ],
                [
                    // H:i:s garantit que SQL comprend qu'il s'agit d'un type TIME
                    'entree'   => $this->formatTime($row['entree']),
                    'sortie'   => $this->formatTime($row['sortie']),
                    'semaine'  => $semaineISO, 
                    'user_id'  => $authUserId,
                ]
            );
        }
    }

    private function formatTime($value)
    {
        if (empty($value)) return null;

        try {
            if (is_numeric($value)) {
                // Conversion de la fraction Excel (ex: 0.375) en format SQL 09:00:00
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i:s');
            }
            // Si c'est une string "09:00", Carbon la transforme en "09:00:00"
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}