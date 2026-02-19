<?php

namespace App\Imports;

use App\Models\Projet;
use App\Models\Site;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ProjetsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // 1. On vérifie si les colonnes critiques sont présentes
            // Attention : On utilise 'site' car c'est le nom dans ton dump
            if (empty($row['site']) || empty($row['designation'])) {
                continue;
            }

            // 2. Récupération propre de l'ID du site (on force l'entier)
            $siteId = (int) $row['site'];

            // 3. Sécurité : On vérifie si le site existe pour éviter une erreur SQL
            if (!Site::where('id', $siteId)->exists()) {
                // Optionnel : tu peux logger l'erreur ici
                continue;
            }

            // 4. Update ou Create pour le Projet
            Projet::updateOrCreate(
                [
                    'designation' => trim($row['designation']),
                    'site_id'     => $siteId
                ],
                [
                    'msa_id'         => $row['msa_id'] ?? null,
                    // Attention à l'orthographe : 'dlt_superviseur' dans Excel vs 'dltsuperviseur' en BDD ?
                    'dltsuperviseur' => $row['dlt_superviseur'] ?? null,
                ]
            );
        }
    }
}