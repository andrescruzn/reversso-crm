<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Users\Infrastructure\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $conductores = User::whereHas('roles', fn($q) => $q->where('name', 'conductor'))->get();

        $startDate = Carbon::now()->subMonths(3);
        $endDate = Carbon::now();

        foreach ($conductores as $conductor) {
            $data = [];
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if ($date->dayOfWeek === Carbon::SATURDAY) continue;

                $isSunday = ($date->dayOfWeek === Carbon::SUNDAY);

                // 80% probabilidad de asistencia
                if (rand(1, 10) > 2) {
                    $entrada = $date->copy()->setTime(rand(6, 8), rand(0, 59));
                    // Aleatoriedad de extras
                    $horas = (rand(1, 10) > 7) ? rand(10, 13) : 9;
                    $salida = $entrada->copy()->addHours($horas);

                    $data[] = [
                        'user_id'    => $conductor->id,
                        'check_in'   => $entrada,
                        'check_out'  => ($date->isToday()) ? null : $salida,
                        'is_holiday' => $isSunday,
                        'status'     => ($date->isToday()) ? 'active' : 'completed',
                        'created_at' => $entrada,
                        'updated_at' => $salida,
                    ];
                }
            }
            DB::table('user_attendance')->insert($data);
        }
        $this->command->info('✅ 3 meses de asistencia para 3 conductores listos.');
    }
}
