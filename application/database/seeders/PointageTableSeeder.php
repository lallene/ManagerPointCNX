<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PointageTableSeeder extends Seeder
{
    public function run()
    {
        // On vide la table pour repartir sur des stats propres
        DB::table('pointages')->truncate();

        DB::table('plannings')->orderBy('id')->chunk(1000, function ($plannings) {
            $pointages = [];

            foreach ($plannings as $planning) {
                $dice = rand(1, 100);

                // 1. ABSENCES (5% de probabilité pour ne pas fausser les graphiques de présence)
                if ($dice <= 5) continue; 

                // Heures prévues (à partir de votre table planning)
                $hEntreePrevue = Carbon::parse($planning->entree);
                $hSortiePrevue = Carbon::parse($planning->sortie);
                $dateJour = $planning->jour;

                // --- LOGIQUE DE RETARD & DÉPASSEMENT (Cible : 3% à 15% de retard par projet) ---
                
                // 88% de probabilité d'être un "Bon Élève" (on vise le milieu de votre fourchette 3-15%)
                if ($dice <= 88) { 
                    $hEntreeReelle = $hEntreePrevue->copy()->addMinutes(rand(-5, 2)); // Pile ou un peu d'avance
                    $hSortieReelle = $hSortiePrevue->copy()->addMinutes(rand(-1, 5)); 
                    $supplementPause = 0;
                } else {
                    // 12% de probabilité de retard/anomalie
                    $hEntreeReelle = $hEntreePrevue->copy()->addMinutes(rand(6, 25)); // Retard significatif
                    $hSortieReelle = $hSortiePrevue->copy()->subMinutes(rand(5, 15)); // Départ anticipé
                    $supplementPause = rand(10, 20); // Dépassement de la pause déjeuner
                }

                // --- GESTION DES PAUSES (Format TIME pour votre SQL) ---
                $pauseDebut = null;
                $pauseFin = null;
                $minutesPause = 0;

                // On génère une pause si le shift dure plus de 5 heures
                if ($hEntreePrevue->diffInHours($hSortiePrevue) >= 5) {
                    $pauseDebut = Carbon::parse($dateJour . ' 12:30:00')->addMinutes(rand(-15, 15));
                    $minutesPause = 60 + $supplementPause; // 1h standard + le dépassement éventuel
                    $pauseFin = $pauseDebut->copy()->addMinutes($minutesPause);
                }

                // --- CALCUL DES MINUTES TRAVAILLÉES ---
                // (Heure Sortie - Heure Entrée) - Durée de la pause
                $presenceTotale = $hEntreeReelle->diffInMinutes($hSortieReelle);
                $travailEffectif = $presenceTotale - $minutesPause;

                $pointages[] = [
                    'agent_id'           => $planning->agent_id,
                    'planning_id'        => $planning->id,
                    'user_id'            => $planning->user_id ?? 1, // ID du validateur
                    'date_pointage'      => $dateJour,
                    'semaine'            => $planning->semaine,
                    // Utilisation de format('H:i:s') pour correspondre au type TIME de votre SQL
                    'entree'             => $hEntreeReelle->format('H:i:s'),
                    'pause_debut'        => $pauseDebut ? $pauseDebut->format('H:i:s') : null,
                    'pause_fin'          => $pauseFin ? $pauseFin->format('H:i:s') : null,
                    'sortie'             => $hSortieReelle->format('H:i:s'),
                    'minutes_travaillees'=> max(0, $travailEffectif),
                    'heure_sup'          => ($dice > 95) ? rand(1, 2) : 0, // 5% de chance d'heures sup
                    'commentaires'       => ($supplementPause > 0) ? "Dépassement pause détecté" : null,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            DB::table('pointages')->insert($pointages);
        });

        $this->command->info("Pointages synchronisés avec succès. Taux de retard stabilisé entre 5% et 12%.");
    }
}