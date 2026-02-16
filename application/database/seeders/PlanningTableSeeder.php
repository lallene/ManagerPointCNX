<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PlanningTableSeeder extends Seeder
{
    public function run()
{
    Schema::disableForeignKeyConstraints();
    DB::table('pointages')->truncate();
    DB::table('plannings')->truncate();
    Schema::enableForeignKeyConstraints();

    // 1️⃣ Cibler uniquement le rôle "Manager"
    $managerRoleId = DB::table('roles')->where('name', 'Manager')->value('id');

    if (!$managerRoleId) {
        $this->command->error("Le rôle 'Manager' n'existe pas.");
        return;
    }

    $userIdsWithRole = DB::table('model_has_roles')
        ->where('role_id', $managerRoleId)
        ->pluck('model_id');

    $agents = DB::table('agents')
        ->join('users', 'agents.work_email', '=', 'users.work_email')
        ->whereIn('users.id', $userIdsWithRole)
        ->select('agents.id', 'agents.workday_id', 'agents.manager')
        ->get();

    // 🕒 Définition des heures de début autorisées (Shifts de 9h)
    $availableStartHours = [6, 7, 8, 9, 10, 11, 12];

    // 📅 Période : 01 Août 2025 au 31 Mars 2026
    $startDate = \Carbon\Carbon::create(2025, 8, 1);
    $endDate   = \Carbon\Carbon::create(2026, 3, 31);

    $data = [];
    $totalInserted = 0;

    foreach ($agents as $agent) {
        $bossUserId = DB::table('agents as m')
            ->join('users as u', 'm.work_email', '=', 'u.work_email')
            ->where('m.workday_id', $agent->manager)
            ->value('u.id') ?? 1;

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            
            // 🎲 Sélection de 2 jours OFF aléatoires pour la semaine
            $weekDays = [0, 1, 2, 3, 4, 5, 6];
            $offKeys = (array) array_rand($weekDays, 2);
            $offDays = array_intersect_key($weekDays, array_flip($offKeys));

            for ($i = 0; $i < 7; $i++) {
                $day = $currentDate->copy()->addDays($i);
                if ($day > $endDate) break;

                if (in_array($day->dayOfWeek, $offDays)) continue;

                // 🕒 Sélection d'un shift fixe parmi ta liste
                $startHour = $availableStartHours[array_rand($availableStartHours)];
                
                $startTime = $day->copy()->setTime($startHour, 0, 0);
                $endTime   = $startTime->copy()->addHours(9); // Sortie toujours à H+9

                $data[] = [
                    'jour'       => $day->format('Y-m-d'),
                    'entree'     => $startTime->format('H:i:s'),
                    'sortie'     => $endTime->format('H:i:s'),
                    'semaine'    => $day->format('Y-W'),
                    'agent_id'   => $agent->id,
                    'user_id'    => $bossUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($data) >= 500) {
                    DB::table('plannings')->insert($data);
                    $totalInserted += count($data);
                    $data = [];
                }
            }
            $currentDate->addWeek();
        }
    }

    if (!empty($data)) {
        DB::table('plannings')->insert($data);
        $totalInserted += count($data);
    }

    $this->command->info("✅ Succès : $totalInserted lignes de planning générées selon tes shifts de 9h.");
}
}
