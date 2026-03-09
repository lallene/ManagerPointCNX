<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Planning;
use App\Models\Pointage;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
          
            // 2. On crée l'utilisateur Admin par défaut
            RoleAndAdminSeeder::class,
            
        ]);
    }
}

