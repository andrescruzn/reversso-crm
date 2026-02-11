<?php

declare(strict_types=1);

namespace App\Common\Enums;

/**
 * Catálogo centralizado de códigos de error del sistema.
 *
 * CONVENCIÓN:
 * - 0: Éxito
 * - 1000-1099: Errores generales
 * - 1100-1199: Errores de usuarios/autenticación
 * - 1200-1299: Errores de logística/tracking
 * - 1300+: Errores de negocio específicos
 */
enum ErrorCode: int
{
    // Éxito
    case SUCCESS = 0;

        // Errores generales (1000-1099)
    case GENERAL_ERROR = 1000;
    case VALIDATION_ERROR = 1001;
    case NOT_FOUND = 1002;
    case UNAUTHORIZED = 1003;
    case FORBIDDEN = 1004;
    case SERVER_ERROR = 1005;

        // Errores de usuario/autenticación (1100-1199)
    case USER_EMAIL_EXISTS = 1100;
    case USER_NOT_FOUND = 1101;
    case USER_INACTIVE = 1102;
    case USER_INVALID_CREDENTIALS = 1103;
    case USER_EMAIL_NOT_VERIFIED = 1104;
    case USER_ROLE_NOT_FOUND = 1105;
    case USER_ALREADY_HAS_ROLE = 1106;

        // Errores de logística/tracking (1200-1299)
    case TRACKING_ALREADY_STARTED = 1200;
    case TRACKING_NOT_STARTED = 1201;
    case TRACKING_ALREADY_ENDED = 1202;
    case TRACKING_INVALID_TIME = 1203;
    case TRACKING_INVALID_ODOMETER = 1204;
    case TRACKING_NOT_FOUND = 1205;
    case TRACKING_ALREADY_APPROVED = 1206;
    case TRACKING_CANNOT_APPROVE_OWN = 1207;

        // Errores de reportes (1300-1399)
    case REPORT_INVALID_DATE_RANGE = 1300;
    case REPORT_NO_DATA = 1301;

    /**
     * Obtener mensaje descriptivo del error.
     */
    public function message(): string
    {
        return match ($this) {
            self::SUCCESS => 'Operación exitosa',
            self::GENERAL_ERROR => 'Error general del sistema',
            self::VALIDATION_ERROR => 'Errores de validación',
            self::NOT_FOUND => 'Recurso no encontrado',
            self::UNAUTHORIZED => 'No autorizado',
            self::FORBIDDEN => 'Acceso prohibido',
            self::SERVER_ERROR => 'Error interno del servidor',
            self::USER_EMAIL_EXISTS => 'El email ya está registrado',
            self::USER_NOT_FOUND => 'Usuario no encontrado',
            self::USER_INACTIVE => 'Usuario inactivo',
            self::USER_INVALID_CREDENTIALS => 'Credenciales inválidas',
            self::USER_EMAIL_NOT_VERIFIED => 'Email no verificado',
            self::USER_ROLE_NOT_FOUND => 'Rol no encontrado',
            self::USER_ALREADY_HAS_ROLE => 'El usuario ya tiene este rol',
            self::TRACKING_ALREADY_STARTED => 'Ya existe un registro de tiempo activo',
            self::TRACKING_NOT_STARTED => 'No hay registro de tiempo activo',
            self::TRACKING_ALREADY_ENDED => 'El registro de tiempo ya fue finalizado',
            self::TRACKING_INVALID_TIME => 'Hora de fin debe ser posterior a hora de inicio',
            self::TRACKING_INVALID_ODOMETER => 'Odómetro final debe ser mayor al inicial',
            self::TRACKING_NOT_FOUND => 'Registro de tiempo no encontrado',
            self::TRACKING_ALREADY_APPROVED => 'El registro ya fue aprobado',
            self::TRACKING_CANNOT_APPROVE_OWN => 'No puedes aprobar tus propios registros',
            self::REPORT_INVALID_DATE_RANGE => 'Rango de fechas inválido',
            self::REPORT_NO_DATA => 'No hay datos para el rango de fechas seleccionado',
        };
    }
}
