<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Users\Infrastructure\Models\User;
use Carbon\Carbon;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $conductores = User::whereHas('roles', fn($q) => $q->where('name', 'conductor'))->get();
        $adminId = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()->id ?? 1;

        $destinos = ['Planta Norte', 'Bodega Central', 'Cliente Éxito', 'Puerto Local', 'Sede Sur'];

        foreach ($conductores as $conductor) {
            $viajes = [];
            $odometer = 10000;

            for ($i = 0; $i < 15; $i++) {
                $start = Carbon::now()->subDays(rand(1, 30))->setTime(rand(8, 16), 0);
                $km = rand(20, 100);

                $viajes[] = [
                    'user_id' => $conductor->id,
                    'start_time' => $start,
                    'end_time' => $start->copy()->addHours(2),
                    'origin' => 'Principal',
                    'destination' => $destinos[array_rand($destinos)],
                    'start_odometer' => $odometer,
                    'end_odometer' => $odometer + $km,
                    'approved_by' => (rand(1, 10) > 5) ? $adminId : (rand(1, 10) > 8 ? 0 : null),
                    'approved_at' => (rand(1, 10) > 5) ? now() : null,
                    'created_at' => $start,
                ];
                $odometer += $km + rand(5, 10);
            }
            DB::table('time_tracking')->insert($viajes);
        }
        $this->command->info('✅ Viajes de tracking generados para los 3 conductores.');
    }
}
