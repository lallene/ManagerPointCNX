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

        // 1️⃣ Récupérer les users ayant le rôle ID 8 (agents)
        $userIdsWithRole = DB::table('model_has_roles')
            ->where('role_id', 8)
            ->pluck('model_id');

        // 2️⃣ Récupérer les agents liés aux users
        $agents = DB::table('agents')
            ->join('users', 'agents.work_email', '=', 'users.work_email')
            ->whereIn('users.id', $userIdsWithRole)
            ->select(
                'agents.id',
                'agents.workday_id',
                'agents.manager',
                'agents.work_email'
            )
            ->get();

        if ($agents->isEmpty()) {
            $this->command->error("Aucun agent trouvé pour le rôle spécifié.");
            return;
        }

        $startDate = Carbon::create(2025, 11, 1);
        $endDate   = Carbon::create(2026, 3, 31);

        $data = [];
        $totalInserted = 0;

        foreach ($agents as $agent) {

            // 🔎 Trouver le user_id du manager via workday_id
            $managerUserId = DB::table('agents as m')
                ->join('users as u', 'm.work_email', '=', 'u.work_email')
                ->where('m.workday_id', $agent->manager)
                ->value('u.id') ?? 1; // fallback

            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {

                // 2 jours off aléatoires par semaine
                $offDays = (array) array_rand([0,1,2,3,4,5,6], 2);

                for ($i = 0; $i < 7; $i++) {

                    $day = $currentDate->copy()->addDays($i);
                    if ($day > $endDate) break;

                    // Jour OFF
                    if (in_array($day->dayOfWeek, $offDays)) continue;

                    $startHour = rand(6, 13);
                    $startTime = $day->copy()->setTime($startHour, 0, 0);
                    $endTime   = $startTime->copy()->addHours(9);

                    $data[] = [
                        'jour'       => $day->format('Y-m-d'),
                        'entree'     => $startTime->format('H:i:s'),
                        'sortie'     => $endTime->format('H:i:s'),
                        'semaine'    => $day->format('Y-W'),
                        'agent_id'   => $agent->id,
                        'user_id'    => $managerUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Batch insert
                    if (count($data) >= 1000) {
                        DB::table('plannings')->insert($data);
                        $totalInserted += count($data);
                        $data = [];
                    }
                }

                $currentDate->addWeek();
            }
        }

        // Insert final
        if (!empty($data)) {
            DB::table('plannings')->insert($data);
            $totalInserted += count($data);
        }

        $this->command->info("✅ Succès : $totalInserted lignes insérées.");
    }
}
