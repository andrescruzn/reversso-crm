<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ejecutar la migración para el control de asistencia (Reloj Checador).
     */
    public function up(): void
    {
        Schema::create('user_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Marcaciones exactas para cálculo de nómina y jornada legal (47h)
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();

            // Flag para recargos dominicales y festivos según legislación CO
            $table->boolean('is_holiday')->default(false);

            // Estado de la sesión laboral: active (en turno), completed (finalizado)
            $table->string('status')->default('active');

            $table->timestamps();

            // Borrado lógico para mantener integridad histórica de auditoría
            $table->softDeletes();

            // Índices optimizados para reportes de nómina y búsqueda de estados activos
            $table->index(['user_id', 'status']);
            $table->index('check_in');
            $table->index('is_holiday');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_attendance');
    }
};
