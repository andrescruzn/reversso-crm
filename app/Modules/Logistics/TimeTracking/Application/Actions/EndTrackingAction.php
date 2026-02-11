<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Application\Actions;

use App\Common\Enums\ErrorCode;
use App\Common\Services\ServiceResult;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;

/**
 * Acción para finalizar un registro de tiempo.
 *
 * PATRÓN: Action Class (Single Responsibility)
 *
 * RESPONSABILIDAD:
 * Finalizar una jornada de trabajo para un conductor.
 */
class EndTrackingAction
{
    public function __construct(
        private readonly TimeTrackingRepositoryInterface $repository
    ) {}

    /**
     * Ejecutar la acción.
     */
    public function execute(
        int $userId,
        string $destination,
        ?float $endOdometer = null,
        ?string $observations = null
    ): ServiceResult {
        // =====================================================================
        // 1. BUSCAR REGISTRO ACTIVO
        // =====================================================================

        $tracking = $this->repository->findActiveByUserId($userId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'No tienes un registro de tiempo activo',
                errorCode: ErrorCode::TRACKING_NOT_STARTED->value
            );
        }

        // =====================================================================
        // 2. VALIDAR ODÓMETRO (SI SE PROPORCIONÓ)
        // =====================================================================

        if ($endOdometer !== null && $tracking->start_odometer !== null) {
            if ($endOdometer < $tracking->start_odometer) {
                return ServiceResult::fail(
                    message: 'El odómetro final debe ser mayor al inicial',
                    errorCode: ErrorCode::TRACKING_INVALID_ODOMETER->value
                );
            }
        }

        // =====================================================================
        // 3. ACTUALIZAR REGISTRO
        // =====================================================================

        $data = [
            'end_time' => now(),
            'destination' => $destination,
            'end_odometer' => $endOdometer,
        ];

        // Combinar observaciones si ya existían
        if ($observations) {
            $existingObs = $tracking->observations ?? '';
            $data['observations'] = trim($existingObs . "\n" . $observations);
        }

        $tracking = $this->repository->update($tracking, $data);

        // =====================================================================
        // 4. RETORNAR RESULTADO EXITOSO
        // =====================================================================

        return ServiceResult::ok(
            data: $tracking,
            message: 'Jornada finalizada exitosamente'
        );
    }
}
