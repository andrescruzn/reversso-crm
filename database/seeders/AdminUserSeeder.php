<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Users\Infrastructure\Models\{Role, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        $admin = User::create([
            'name' => 'Admin Reversso',
            'email' => 'admin@reversso.com',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(Role::where('name', 'admin')->first());

        // LOS 3 CONDUCTORES
        $conductores = [
            ['name' => 'Juan Conductor', 'email' => 'juan@reversso.com'],
            ['name' => 'Pedro Conductor', 'email' => 'pedro@reversso.com'],
            ['name' => 'Maria Conductor', 'email' => 'maria@reversso.com'],
        ];

        foreach ($conductores as $c) {
            $user = User::create([
                'name' => $c['name'],
                'email' => $c['email'],
                'password' => Hash::make('conductor123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole(Role::where('name', 'conductor')->first());
        }

        $this->command->info('✅ Admin y 3 Conductores creados.');
    }
}
