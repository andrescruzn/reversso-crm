<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Application\Actions;

use App\Common\Enums\ErrorCode;
use App\Common\Services\ServiceResult;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use Carbon\Carbon;

/**
 * Acción para iniciar un registro de tiempo.
 *
 * PATRÓN: Action Class (Single Responsibility)
 *
 * RESPONSABILIDAD:
 * Iniciar una jornada de trabajo para un conductor.
 */
class StartTrackingAction
{
    public function __construct(
        private readonly TimeTrackingRepositoryInterface $repository
    ) {}

    /**
     * Ejecutar la acción.
     */
    public function execute(
        int $userId,
        string $origin,
        ?float $startOdometer = null,
        bool $isHoliday = false,
        ?string $observations = null
    ): ServiceResult {
        // =====================================================================
        // 1. VALIDAR QUE NO EXISTA REGISTRO ACTIVO
        // =====================================================================

        if ($this->repository->hasActiveTracking($userId)) {
            return ServiceResult::fail(
                message: 'Ya tienes un registro de tiempo activo. Finalízalo antes de iniciar uno nuevo.',
                errorCode: ErrorCode::TRACKING_ALREADY_STARTED->value
            );
        }

        // =====================================================================
        // 2. CREAR REGISTRO DE TIEMPO
        // =====================================================================

        $tracking = $this->repository->create([
            'user_id' => $userId,
            'start_time' => now(),
            'origin' => $origin,
            'start_odometer' => $startOdometer,
            'is_holiday' => $isHoliday,
            'observations' => $observations,
        ]);

        // =====================================================================
        // 3. RETORNAR RESULTADO EXITOSO
        // =====================================================================

        return ServiceResult::ok(
            data: $tracking,
            message: 'Jornada iniciada exitosamente'
        );
    }
}
