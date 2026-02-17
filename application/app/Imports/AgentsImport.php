<?php

namespace App\Imports;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AgentsImport implements ToModel, WithChunkReading, WithHeadingRow, SkipsOnError
{
    public function model(array $row)
    {
        $groups = [
            'Directeur'   => ['JC0603', 'JC2550', 'JC0600', 'JC0881', 'JT0323', 'JC0091'],
            'Top Manager' => ['JC0630', 'JC0605', 'JC0604', 'JC3370', 'JC1655', 'JC0801'],
            'Manager'     => ['JC1704', 'JC2555', 'JC1619', 'JC3375', 'JC1618', 'JC0629', 'JC0634', 'JC2770', 'JC0631', 'JC3221', 'JC2757', 'JC1705', 'JC1767'],
            'RH'          => ['JC0879', 'JC0877', 'JC0203', 'JC0878','JC0868' ,'JC2714'],
        ];

        $jobCode = $row['job_code'] ?? null;
        $roleName = null;
        foreach ($groups as $name => $codes) {
            if (in_array($jobCode, $codes)) {
                $roleName = $name;
                break;
            }
        }

        $matricule = $row['id_wd'] ?? null;
        $email = $row['work_email'] ?? null;
        $fonction = $row['business_title'] ?? null;

        if (!$roleName || empty($matricule) || empty($email) || empty($fonction)) {
            return null;
        }

// La chaîne : "Operations Group - Contact Center - Advisor (Esse Lesly Melissa Mbesso (102573185))"
$hierarchie = $row['organisation_hierarchique'] ?? '';
$managerId = null;

// Regex adaptée pour capturer les chiffres à l'intérieur des doubles parenthèses finales
if (preg_match('/\((\d+)\)\)$/', trim($hierarchie), $matches)) {
    $managerId = $matches[1]; // Résultat : 102573185
} 
// Repli de sécurité au cas où il n'y aurait qu'un seul niveau de parenthèses
elseif (preg_match('/\((\d+)\)$/', trim($hierarchie), $matches)) {
    $managerId = $matches[1];
}

//dd($managerId);
        $projetId = DB::table('projets')->where('msa_id', $row['msa_id'] ?? null)->value('id') 
                    ?? DB::table('projets')->where('designation', 'Autre')->value('id');

        DB::transaction(function () use ($row, $roleName, $matricule, $email, $fonction, $projetId, $managerId) {
            $agent = Agent::updateOrCreate(
                ['workday_id' => $matricule],
                [
                    'nom'        => $row['nom'],
                    'prenom'     => $row['prenom'],
                    'work_email' => $email,
                    'fonction'   => $fonction,
                    'manager'    => $managerId, // ID extrait dynamiquement
                ]
            );

            $agent->projets()->syncWithoutDetaching([$projetId]);

            $user = User::updateOrCreate(
                ['work_email' => $email],
                [
                    'name'     => $row['prenom'] . ' ' . $row['nom'],
                    'password' => Hash::make($matricule),
                    'password_first_connection' => true,
                ]
            );

            $user->syncRoles($roleName);
        });

        return null;
    }

    public function headingRow(): int { return 1; }
    public function batchSize(): int { return 1000; }
    public function chunkSize(): int { return 1000; }

    public function onError(\Throwable $e) {
        Log::error('Erreur Import Manager extraction:', ['msg' => $e->getMessage()]);
    }
}