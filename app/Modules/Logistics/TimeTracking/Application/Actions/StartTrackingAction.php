<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Application\Actions;

use App\Common\Services\ServiceResult;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;

/**
 * Caso de uso: iniciar tracking.
 *
 * RESPONSABILIDAD:
 * - Validar reglas de negocio del inicio
 * - Persistir el tracking inicial (incluyendo placa)
 */
class StartTrackingAction
{
    public function __construct(
        private readonly TimeTrackingRepositoryInterface $repository
    ) {}

    public function execute(
        int $userId,
        string $vehiclePlate,     // ✅ NUEVO
        string $origin,
        ?float $startOdometer = null,
        bool $isHoliday = false,
        ?string $observations = null
    ): ServiceResult {
        // ------------------------------------------------------------
        // Reglas/validaciones de negocio propias del caso de uso
        // (ej: no permitir 2 activos, etc.) -> si ya las tienes, déjalas
        // ------------------------------------------------------------

        // Persistencia: asegúrate de enviar vehicle_plate al repo
        $tracking = $this->repository->create([
            'user_id'        => $userId,
            'start_time'     => now(),
            'origin'         => $origin,
            'vehicle_plate'  => $vehiclePlate,          // ✅ CLAVE
            'start_odometer' => (int) ($startOdometer ?? 0),
            'is_holiday'     => $isHoliday,
            'observations'   => $observations,
        ]);

        return ServiceResult::ok(
            data: $tracking,
            message: 'Viaje iniciado.'
        );
    }
}
