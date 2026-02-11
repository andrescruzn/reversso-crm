<?php

declare(strict_types=1);

namespace App\Common\Enums;

/**
 * Roles del sistema REVERSSO CRM.
 *
 * ROLES:
 * - ADMIN: Acceso total al sistema
 * - CONDUCTOR: Acceso a funciones de tracking
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case CONDUCTOR = 'conductor';

    /**
     * Obtener nombre para mostrar del rol.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::CONDUCTOR => 'Conductor',
        };
    }

    /**
     * Obtener descripción del rol.
     */
    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Acceso total al sistema, dashboard web, aprobación de registros',
            self::CONDUCTOR => 'Acceso a funciones de tracking desde mobile app',
        };
    }

    /**
     * Verificar si es rol de administrador.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Verificar si es rol de conductor.
     */
    public function isConductor(): bool
    {
        return $this === self::CONDUCTOR;
    }

    /**
     * Obtener todos los valores de roles como array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
