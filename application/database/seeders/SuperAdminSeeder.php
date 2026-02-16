<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
 
        $user = User::create([
            'name' => 'Super Admin',
            'work_email' => 'admin@concentrix.com',
            'password' => Hash::make('password'),
            'password_first_connection' => 0,
        ]);

        $user->assignRole(7);

        $this->command->info('✅ SuperAdmin créé avec succès.');
    }
}
