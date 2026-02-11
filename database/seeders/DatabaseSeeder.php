<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,      // Primero los roles
            AdminUserSeeder::class, // Luego los usuarios con sus roles
            AttendanceSeeder::class, // Luego la asistencia
            LogisticsSeeder::class, // Al final el tracking
        ]);
    }
}
