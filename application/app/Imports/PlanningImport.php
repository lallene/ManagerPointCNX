<?php

namespace App\Imports;

use App\Models\Agent;
use App\Models\Planning;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PlanningImport implements ToCollection
{


public function collection(Collection $rows)
{
    $rows->shift(); // en-tête

    foreach ($rows as $row) {
        $agent = Agent::where('workday_id', $row[0])->first();
        if (!$agent) {
            continue;
        }

        // Convertir le jour en DateTime
        $jourDateTime = Date::excelToDateTimeObject($row[1]);

        // Ne rien faire si la date est aujourd’hui ou dans le passé
        if ($jourDateTime <= Carbon::today()) {
            continue;
        }

        $jour = $jourDateTime->format('Y-m-d');
        $heureDebut = Date::excelToDateTimeObject($row[2])->format('H:i');
        $heureFin = Date::excelToDateTimeObject($row[3])->format('H:i');
        $commentaire = $row[4] ?? null;

        Planning::updateOrCreate(
            [
                'agent_id' => $agent->id,
                'jour' => $jour,
            ],
            [
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
                'semaine' => Carbon::parse($jour)->isoWeek(),
                'Commentaire' => $commentaire,
                'user_id' => Auth::id(),
            ]
        );
    }
}





}
