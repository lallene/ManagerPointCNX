<?php

namespace App\Imports;

use App\Models\Agent;
use App\Models\User;
use App\Models\Projet; 
use App\Models\Site;   
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
        // 1. Définition des rôles (inchangé)
        $groups = [
            'Directeur'   => ['JC0603', 'JC2550', 'JC0600', 'JC0881', 'JT0323', 'JC0091'],
            'Top Manager' => ['JC0630', 'JC0605', 'JC0604', 'JC3370', 'JC1655', 'JC0801', 'JC0606'],
            'Manager'     => ['JC3224','JC3415','JC2913','JC0008','JC1704', 'JC2555', 'JC1619', 'JC3375', 'JC1618', 'JC0629', 'JC0634', 'JC2770', 'JC0631', 'JC3221', 'JC2757', 'JC1705', 'JC1767'],
            'RH'          => ['JC0879', 'JC0877', 'JC0203', 'JC0878', 'JC0868', 'JC2714', 'JC0828'],
        ];

        $jobCode = $row['job_code'] ?? null;
        $roleName = 'Manager';

        foreach ($groups as $name => $codes) {
            if (in_array($jobCode, $codes)) {
                $roleName = $name;
                break;
            }
        }

        // 2. Vérification des données critiques de l'Agent
        $matricule = $row['id_wd'] ?? null;
        $email = $row['work_email'] ?? null;
        $fonction = $row['business_title'] ?? null;

        if (empty($matricule) || empty($email) || empty($fonction)) {
            return null;
        }

        // 3. Logique d'importation/création du Projet
        $projetId = null;
        $siteId = isset($row['site']) ? (int) $row['site'] : null;

        // On ne crée/update le projet que si on a une désignation et un site valide
        if (!empty($row['designation']) && $siteId && Site::where('id', $siteId)->exists()) {
            $projet = Projet::updateOrCreate(
                [
                    'designation' => trim($row['designation']),
                    'site_id'     => $siteId
                ],
                [
                    'msa_id'         => $row['msa_id'] ?? null,
                    'dltsuperviseur' => $row['dlt_superviseur'] ?? null,
                ]
            );
            $projetId = $projet->id;
        }

        // Repli sur "Autre" si le projet n'a pas pu être identifié/créé
        if (!$projetId) {
            $projetId = Projet::where('designation', 'Autre')->value('id');
        }

        // 4. Extraction du Manager ID
        $hierarchie = $row['organisation_hierarchique'] ?? '';
        $managerId = null;
        if (preg_match('/\((\d+)\)$/', trim($hierarchie), $matches)) {
            $managerId = $matches[1];
        }

        // 5. Transaction pour l'Agent et l'User
        DB::transaction(function () use ($row, $roleName, $matricule, $email, $fonction, $projetId, $managerId) {

            $agent = Agent::updateOrCreate(
                ['workday_id' => $matricule],
                [
                    'nom'        => $row['nom'] ?? '',
                    'prenom'     => $row['prenom'] ?? '',
                    'work_email' => $email,
                    'fonction'   => $fonction,
                    'manager'    => $managerId,
                ]
            );

            if ($projetId) {
                $agent->projets()->syncWithoutDetaching([$projetId]);
            }

            $user = User::updateOrCreate(
                ['work_email' => $email],
                [
                    'name'     => ($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''),
                    'password' => Hash::make($matricule), // Attention: change le mdp à chaque import si l'user existe déjà
                    'password_first_connection' => true,
                ]
            );

            $user->syncRoles([$roleName]);
        });

        return null;
    }

    // ... (méthodes headingRow, batchSize, etc. identiques)
    
    public function headingRow(): int { return 1; }
    public function batchSize(): int { return 1000; }
    public function chunkSize(): int { return 1000; }

    public function onError(\Throwable $e)
    {
        Log::error('Erreur Import Agents', ['message' => $e->getMessage()]);
    }
}