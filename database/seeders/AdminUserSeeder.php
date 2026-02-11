<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder para crear usuario administrador de prueba.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Ejecutar seeder.
     */
    public function run(): void
    {
        // =====================================================================
        // CREAR USUARIO ADMIN
        // =====================================================================

        $admin = User::create([
            'name' => 'Admin Reversso',
            'email' => 'admin@reversso.com',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // =====================================================================
        // ASIGNAR ROL DE ADMIN
        // =====================================================================

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        // =====================================================================
        // CREAR USUARIO CONDUCTOR DE PRUEBA
        // =====================================================================

        $conductor = User::create([
            'name' => 'Juan Conductor',
            'email' => 'conductor@reversso.com',
            'password' => Hash::make('conductor123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // =====================================================================
        // ASIGNAR ROL DE CONDUCTOR
        // =====================================================================

        $conductorRole = Role::where('name', 'conductor')->first();

        if ($conductorRole) {
            $conductor->assignRole($conductorRole);
        }

        $this->command->info('✅ Usuarios de prueba creados:');
        $this->command->info('   Admin: admin@reversso.com / admin123');
        $this->command->info('   Conductor: conductor@reversso.com / conductor123');
    }
}
