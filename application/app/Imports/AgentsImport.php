<?php

namespace App\Imports;

use App\Models\Agent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AgentsImport implements ToModel, WithChunkReading, WithHeadingRow, SkipsOnError
{

    public function model(array $row)
{
    // Liste autorisée de job_code
    $allowedJobCodes = [
        'JC0603', 'JC0879', 'JC0630', 'JC0801', 'JC1704', 'JC0605', 'JC2555', 'JC0604',
        'JC1619', 'JC1767', 'JC1655', 'JC3375', 'JC0877', 'JC1618', 'JC2550', 'JC0629',
        'JC0634', 'JC3370', 'JC2770', 'JC0631',  'JC3221', 'JC2757',
    ];



    // Mapping job_code -> role_id
    $roleMapping = [
        'JC1704' => 8, 'JC2555' => 8, 'JC1619' => 8, 'JC3375' => 8, 'JC1618' => 8,
        'JC0629' => 8, 'JC0634' => 8, 'JC2770' => 8, 'JC0631' => 8, 'JC3221' => 8,
        'JC2757' => 8, 'JC1705' => 8, 'JC2550' => 8, 'JC1767' => 8,

        'JC0603' => 9, 'JC0630' => 9, 'JC0605' => 9, 'JC0604' => 9,
        'JC3370' => 9, 'JC1655' => 9, 'JC0801' => 9,

        'JC0879' => 2, 'JC0877' => 2,
    ];

    // Ignorer si job_code absent ou non autorisé
    if (!isset($row['job_code']) || !in_array($row['job_code'], $allowedJobCodes)) {
        Log::info('Skipped row due to job_code not in allowed list:', ['job_code' => $row['job_code'] ?? null]);
        return null;
    }

    if (empty($row['matricule_du_salarie']) || empty($row['nom']) || empty($row['prenom'])) {
        Log::warning('Invalid row skipped:', $row);
        return null;
    }

    // Récupération du projet
    $row['projet'] = DB::table('projets')->where('msa_id', $row['msa_id'] ?? null)->value('id') ?? 113;




    DB::transaction(function () use ($row, $roleMapping) {
        try {
            $agent = Agent::where('workday_id', $row['matricule_du_salarie'])->first();

            if (!$agent) {
                $agent = Agent::create([
                    'workday_id' => $row['matricule_du_salarie'],
                    'projet_id' => $row['projet'],
                    'nom' => $row['nom'],
                    'prenom' => $row['prenom'],
                    'email' => $row['work_email'] ?? null,
                    'fonction' => $row['business_title'] ?? null,
                    'manager' => $row['manager_level_01_employee_id'] ?? null,
                ]);
                Log::info('Agent created:', $agent->toArray());
            } else {
                $agent->update([
                    'projet_id' => $row['projet'],
                    'manager' => $row['manager_level_01_employee_id'] ?? null,
                    'fonction' => $row['business_title'] ?? null,
                ]);
                Log::info('Agent updated:', ['workday_id' => $row['matricule_du_salarie']]);
            }

            // Créer le User si non existant
            if (!empty($row['work_email']) && !\App\Models\User::where('email', $row['work_email'])->exists()) {
                $user = \App\Models\User::create([
                    'name' => $row['prenom'] . ' ' . $row['nom'],
                    'email' => $row['work_email'],
                    'password' => \Hash::make($row['matricule_du_salarie']),
                    'password_first_connection' => true,
                ]);

                // Assignation du rôle selon job_code
                $jobCode = $row['job_code'];
                if (isset($roleMapping[$jobCode])) {
                    $user->assignRole($roleMapping[$jobCode]);
                    Log::info('User created with role:', [
                        'email' => $user->email,
                        'role_id' => $roleMapping[$jobCode],
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing row:', [
                'error' => $e->getMessage(),
                'row' => $row,
            ]);
            throw $e;
        }
    });

    return null;
}



    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onError(\Throwable $e)
    {
        Log::error('Global import error:', ['message' => $e->getMessage()]);
    }
}
