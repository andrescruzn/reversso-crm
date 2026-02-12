<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_tracking', function (Blueprint $table) {
            $table->id();

            // ==========================================================
            // RELACIÓN
            // ==========================================================
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // ==========================================================
            // TIEMPOS
            // ==========================================================
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            // ==========================================================
            // RUTA / VEHÍCULO
            // ==========================================================
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('vehicle_plate', 10)->nullable();

            // ==========================================================
            // ODOMETRÍA
            // ==========================================================
            $table->unsignedInteger('start_odometer')->default(0);
            $table->unsignedInteger('end_odometer')->nullable();

            // ==========================================================
            // METADATA
            // ==========================================================
            $table->boolean('is_holiday')->default(false);
            $table->text('observations')->nullable();

            // ==========================================================
            // AUDITORÍA / APROBACIÓN
            //
            // Reglas (recomendadas):
            // - PENDIENTE: approved_by = NULL, approved_at = NULL
            // - APROBADO:  approved_by = {admin_id}, approved_at != NULL
            // - DESAPROB:  approved_by = 0, approved_at = NULL
            // ==========================================================
            $table->unsignedBigInteger('approved_by')->nullable()->default(null);
            $table->dateTime('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ==========================================================
            // ÍNDICES
            // ==========================================================
            $table->index(['user_id', 'start_time']);
            $table->index(['end_time']);
            $table->index(['approved_by', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_tracking');
    }
};
