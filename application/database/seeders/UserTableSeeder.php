<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;


class UserTableSeeder extends Seeder
{
   public function run()
{
    $roleMapping = [
         "DIRECTEUR D'ACTIVITE" => "Top Manager",
            "DIRECTEUR RH FILIALE" => "RH",
            "HR BUSINESS PARTNER" => "RH",
            'SUPERVISEUR SENIOR' => "Top Manager",
            'ASSISTANT(E) RH' => "RH",
            "RESPONSABLE D'ACTIVITE" => "Top Manager",
            'FORMATEUR METIER' => "Manager",
            'CHEF DE PROJETS' => "Top Manager",
            'Supervisor Quality, Trainee (Non Agent)' => "Manager",
            'CONTROLEUR QUALITE PRODUCTION' => "Manager",
            'TECHNICIEN INFORMATIQUE' => "IT",
            'RESPONSABLE TECHNIQUE SITES' => "IT",
            'INGENIEUR QUALITE FORMATION' => "Manager",
            'Formateur Métier Senior' => "Top Manager",
            'EXPERT METIER' => "Manager",
            'Contrôleur Qualité Mission' => "Manager",
            'LOCAL IT TEAM LEADER' => "IT",
            'FORMATEUR METIER SENIOR' => "Top Manager",
            'Quality Lead' => "Manager",
            'DIRECTEUR DE SITE' => "Directeur",
            'Team Leader Trainee, Operations' => "Manager",
            'Superviseur' => "Manager",
            'Expert Produit Mission' => "Manager",
            'Trainer II' => "Manager",
            'CHEF DE PROJETS AMEL CONT SITE' => "Manager",
            'CHEF DE PROJETS RH' => "RH",
            'People Solutions Generalist I' => "RH",
            'RESPONSABLE QUALITE FORMATION' => "Top Manager",
            'Sr. Quality Evaluator' => "Manager",
            'Responsable opération RH' => "RH",
            'Superviseur Senior (mission)' => "Manager",
            'Trainer I' => "Manager",
            'Contrôleur Qualité Production (mission)' => "Manager",
            'Team Leader, Operations' => "Manager",
            'Quality Evaluator - Trainee (Agent)' => "Manager",
            'Contrôleur Qualité (mission)' => "Manager",
            'Expert Produit' => "Manager",
            'CONTROLEUR QUALITE' => "Manager",
            'Representative, People Solutions' => "RH",
            'ASSISTANTE RH' => "RH",
            'SUBSIDIARIES CEO' => "Directeur",
    ];

    $agents = DB::table('agents')->get();

    foreach ($agents as $agent) {
        // On nettoie la chaîne pour la comparaison (Majuscules + retrait espaces inutiles)
        $fonctionAgent = trim(strtoupper($agent->fonction));
        
        // On cherche le rôle (on transforme aussi les clés du mapping en majuscules pour comparer)
        $roleName = null;
        foreach ($roleMapping as $key => $role) {
            if (strtoupper($key) === $fonctionAgent) {
                $roleName = $role;
                break;
            }
        }

        if ($roleName && !User::where('work_email', $agent->work_email)->exists()) {
            $user = User::create([
                'name' => $agent->prenom . ' ' . $agent->nom,
                'work_email' => $agent->work_email,
                'password' => Hash::make($agent->workday_id),
                'password_first_connection' => true,
            ]);

            $user->assignRole($roleName);
        }
    }
}
}