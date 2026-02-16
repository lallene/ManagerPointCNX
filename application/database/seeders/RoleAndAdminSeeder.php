<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Nettoyer le cache des permissions (important avec Spatie)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. CRÉATION DES RÔLES
        $roles = [
            'IT',
            'Directeur',
            'Top Manager',
            'RH',
            'Manager',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->command->info("Rôles créés avec succès.");

        // 2. CRÉATION DU COMPTE SUPER ADMIN (TOI)
        // Remplace par tes vraies infos
        $admin = User::updateOrCreate(
            ['work_email' => 'admin@concentrix.com'], 
            [
                'name' => 'ADMIN IT',
                'password' => Hash::make('Admin2026!'), 
                'password_first_connection' => false, 
                'email_verified_at' => now(),
            ]
        );

        // On t'assigne le rôle IT
        $admin->syncRoles(['IT']);

        $this->command->info("Compte Super Admin créé : admin@concentrix.com / Admin2026!");
    }
}