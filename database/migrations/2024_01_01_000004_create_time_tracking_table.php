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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Tiempos exactos para cálculo de horas extras (Jornada 47h)
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            $table->string('origin')->nullable();
            $table->string('destination')->nullable();

            // Kilometraje: unsigned para evitar valores negativos
            $table->unsignedInteger('start_odometer')->default(0);
            $table->unsignedInteger('end_odometer')->nullable();

            $table->boolean('is_holiday')->default(false); // Flag para dominicales
            $table->text('observations')->nullable();

            // Auditoría administrativa
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices optimizados para reportes de administración
            $table->index(['user_id', 'start_time']);
            $table->index('is_holiday');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_tracking');
    }
};
