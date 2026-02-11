<?php

declare(strict_types=1);

namespace App\Common\Enums;

/**
 * Estados de registro de tiempo (Time Tracking).
 *
 * ESTADOS:
 * - IN_PROGRESS: Registro iniciado pero no finalizado
 * - COMPLETED: Registro finalizado pero no aprobado
 * - APPROVED: Registro finalizado y aprobado
 */
enum TrackingStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case APPROVED = 'approved';

    /**
     * Obtener nombre para mostrar del estado.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'En Progreso',
            self::COMPLETED => 'Completado',
            self::APPROVED => 'Aprobado',
        };
    }

    /**
     * Obtener color para UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'blue',
            self::APPROVED => 'green',
        };
    }

    /**
     * Verificar si está en progreso.
     */
    public function isInProgress(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    /**
     * Verificar si está completado.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Verificar si está aprobado.
     */
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }
}
