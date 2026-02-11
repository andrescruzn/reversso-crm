<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// ✅ Usamos el modelo modular definido en tu arquitectura
use App\Modules\Users\Infrastructure\Models\Role;

/**
 * Seeder para roles del sistema Reversso.
 * * RESPONSABILIDAD: Definir los perfiles de acceso base.
 * PATRÓN: updateOrCreate para garantizar idempotencia.
 */
class RoleSeeder extends Seeder
{
    /**
     * Ejecutar seeder.
     */
    public function run(): void
    {
        // ======================================================================
        // DEFINICIÓN DE ROLES
        // Se utiliza el 'slug' (name) como llave única para evitar duplicados.
        // ======================================================================
        $roles = [
            [
                'name'         => 'admin',
                'display_name' => 'Administrador',
                'description'  => 'Acceso total al sistema, dashboard web, aprobación de registros',
            ],
            [
                'name'         => 'conductor',
                'display_name' => 'Conductor',
                'description'  => 'Acceso a funciones de tracking desde mobile app',
            ],
        ];

        foreach ($roles as $roleData) {
            // ✅ updateOrCreate busca por 'name', si existe actualiza, si no crea.
            // Esto evita errores de SQL y cumple con el principio DRY.
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}
