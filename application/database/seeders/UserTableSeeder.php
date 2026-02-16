<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    public function run()
    {
        // 1. Récupérer uniquement les agents qui ont une adresse @concentrix.com
        // On utilise LIKE pour filtrer au niveau de la requête SQL (plus performant)
        $agents = DB::table('agents')
                    ->where('work_email', 'LIKE', '%@concentrix.com')
                    ->get();

        $this->command->info("Filtrage terminé : " . $agents->count() . " agents valides trouvés avec une adresse @concentrix.com.");

        foreach ($agents as $agent) {
            // Vérification double au cas où (sécurité)
            $userExists = DB::table('users')->where('work_email', $agent->work_email)->exists();

            if (!$userExists) {
                
                $nomComplet = trim($agent->nom . ' ' . ($agent->prenom ?? $agent->prenoms ?? ''));

                DB::table('users')->insert([
                    'name'                       => $nomComplet,
                    'work_email'                 => $agent->work_email,
                    // Utilisation du workday_id comme mot de passe initial (haché)
                    'password'                   => Hash::make($agent->workday_id), 
                    'password_first_connection'  => 1,
                    'email_verified_at'          => now(),
                    'remember_token'             => Str::random(10),
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }
        }

        $this->command->info("La table Users a été peuplée uniquement avec les comptes @concentrix.com.");
    }
}