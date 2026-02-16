<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PointageTableSeeder extends Seeder
{
    public function run()
    {
        // Nettoyage de la table pour éviter les doublons lors des tests
        DB::table('pointages')->truncate();

        // On traite les 16 693 lignes par paquets de 1000 pour la performance Docker
        DB::table('plannings')->orderBy('id')->chunk(1000, function ($plannings) {
            $pointages = [];

            foreach ($plannings as $planning) {
                // Détermination du statut (Simulation réelle)
                $dice = rand(1, 100);

                // 1. ABSENCE (10% de probabilité)
                if ($dice <= 10) {
                    continue; 
                }

                $hEntreePrevue = Carbon::parse($planning->entree);
                $hSortiePrevue = Carbon::parse($planning->sortie);

                // 2. HEURES SUPPLÉMENTAIRES (10% de probabilité)
                if ($dice > 90) {
                    $hSortiePrevue->addHours(rand(1, 2));
                }

                // 3. POINTAGE NORMAL (Léger décalage humain de quelques minutes)
                // On crée des objets Carbon pour manipuler les heures réelles
                $hEntreeReelle = $hEntreePrevue->copy()->addMinutes(rand(-5, 10));
                $hSortieReelle = $hSortiePrevue->copy()->addMinutes(rand(-5, 5));

                // Calcul des minutes travaillées pour la colonne 'minutes_travaillees'
                $minutesTravaillees = $hEntreeReelle->diffInMinutes($hSortieReelle);

                $pointages[] = [
                    'agent_id'           => $planning->agent_id,
                    'planning_id'        => $planning->id,
                    'user_id'            => $planning->user_id ?? $planning->agent_id,
                    'date_pointage'      => $planning->jour, // Correspondance jour -> date_pointage
                    'semaine'            => $planning->semaine,
                    'entree'             => $hEntreeReelle->format('H:i:s'),
                    'sortie'             => $hSortieReelle->format('H:i:s'),
                    'minutes_travaillees'=> $minutesTravaillees,
                    'commentaires'       => $dice > 90 ? 'Suppléments' : null,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            DB::table('pointages')->insert($pointages);
        });

        $this->command->info("Pointages générés avec succès sur la base du planning.");
    }
}