<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// ✅ IMPORTANTE: Apuntar al modelo correcto en tu arquitectura modular
use App\Modules\Users\Infrastructure\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ❌ BORRA o comenta la línea que causa el error:
        // User::factory(10)->create();

        // ✅ LLAMA a tus seeders específicos
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
