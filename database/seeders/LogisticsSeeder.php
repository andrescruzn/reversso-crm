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
        $conductores = User::all();
        $placas = ['UGW059', 'RDZ902', 'JKX441', 'LPQ009'];

        foreach ($conductores as $conductor) {
            $odometer = 10000;
            for ($i = 0; $i < 12; $i++) {
                $start = Carbon::now()->subDays(rand(1, 20))->setTime(rand(8, 15), 0);
                $km = rand(15, 60);

                DB::table('time_tracking')->insert([
                    'user_id' => $conductor->id,
                    'start_time' => $start,
                    'end_time' => $start->copy()->addHours(2),
                    'origin' => 'Bodega Central',
                    'destination' => 'Punto de Entrega',
                    'vehicle_plate' => $placas[array_rand($placas)],
                    'start_odometer' => $odometer,
                    'end_odometer' => $odometer + $km,
                    'created_at' => $start,
                ]);
                $odometer += $km + 10;
            }
        }
    }
}
