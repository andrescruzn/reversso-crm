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
        // ============================================================
        // 1) OBTENER IDS DE ROLES (según tu tabla roles)
        //    - tú buscas por "name": admin / conductor
        // ============================================================
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $conductorRoleId = Role::where('name', 'conductor')->value('id');

        if (!$adminRoleId) {
            throw new \RuntimeException("No existe el rol con name='admin' en la tabla roles.");
        }

        if (!$conductorRoleId) {
            throw new \RuntimeException("No existe el rol con name='conductor' en la tabla roles.");
        }

        // ============================================================
        // 2) ADMIN (usar updateOrCreate para poder re-seedear sin romper)
        // ============================================================
        $admin = User::updateOrCreate(
            ['email' => 'admin@reversso.com'],
            [
                'name' => 'Admin Reversso',
                'password' => Hash::make('admin123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // ✅ Asignación por pivot (sin Spatie)
        $admin->roles()->sync([$adminRoleId]);

        // ============================================================
        // 3) LOS 3 CONDUCTORES
        // ============================================================
        $conductores = [
            ['name' => 'Juan Conductor',  'email' => 'juan@reversso.com'],
            ['name' => 'Pedro Conductor', 'email' => 'pedro@reversso.com'],
            ['name' => 'Maria Conductor', 'email' => 'maria@reversso.com'],
        ];

        foreach ($conductores as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'password' => Hash::make('conductor123'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // ✅ Asignación por pivot (sin Spatie)
            $user->roles()->sync([$conductorRoleId]);
        }

        $this->command->info('✅ Admin y 3 Conductores creados (sin Spatie).');
    }
}
