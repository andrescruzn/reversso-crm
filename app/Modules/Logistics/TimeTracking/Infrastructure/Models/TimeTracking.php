<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Infrastructure\Models;

use App\Common\Enums\TrackingStatus;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de registro de tiempo (Time Tracking).
 *
 * RESPONSABILIDAD:
 * Representar logs de entrada/salida de conductores.
 */
class TimeTracking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla.
     */
    protected $table = 'time_tracking';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'origin',
        'destination',
        'start_odometer',
        'end_odometer',
        'is_holiday',
        'observations',
        'approved_by',
        'approved_at',
    ];

    /**
     * Atributos que deben ser casteados.
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'start_odometer' => 'decimal:2',
            'end_odometer' => 'decimal:2',
            'is_holiday' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // =====================================================================
    // RELACIONES
    // =====================================================================

    /**
     * Usuario (conductor) que creó este registro.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuario (admin) que aprobó este registro.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =====================================================================
    // MÉTODOS DE UTILIDAD
    // =====================================================================

    /**
     * Obtener el estado del registro.
     */
    public function getStatus(): TrackingStatus
    {
        if ($this->approved_at !== null) {
            return TrackingStatus::APPROVED;
        }

        if ($this->end_time !== null) {
            return TrackingStatus::COMPLETED;
        }

        return TrackingStatus::IN_PROGRESS;
    }

    /**
     * Verificar si el registro está en progreso.
     */
    public function isInProgress(): bool
    {
        return $this->end_time === null;
    }

    /**
     * Verificar si el registro está completado.
     */
    public function isCompleted(): bool
    {
        return $this->end_time !== null && $this->approved_at === null;
    }

    /**
     * Verificar si el registro está aprobado.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Calcular duración en horas.
     */
    public function getDurationInHours(): ?float
    {
        if ($this->end_time === null) {
            return null;
        }

        return $this->start_time->diffInHours($this->end_time, true);
    }

    /**
     * Calcular kilómetros recorridos.
     */
    public function getKilometers(): ?float
    {
        if ($this->start_odometer === null || $this->end_odometer === null) {
            return null;
        }

        return (float) ($this->end_odometer - $this->start_odometer);
    }
}
