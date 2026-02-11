<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Application\Actions;

use App\Common\Enums\ErrorCode;
use App\Common\Services\ServiceResult;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;

/**
 * Acción para aprobar un registro de tiempo.
 *
 * PATRÓN: Action Class (Single Responsibility)
 *
 * RESPONSABILIDAD:
 * Aprobar administrativamente un registro de tiempo completado.
 */
class ApproveTrackingAction
{
    public function __construct(
        private readonly TimeTrackingRepositoryInterface $repository
    ) {}

    /**
     * Ejecutar la acción.
     */
    public function execute(int $trackingId, int $approvedBy): ServiceResult
    {
        // =====================================================================
        // 1. BUSCAR REGISTRO
        // =====================================================================

        $tracking = $this->repository->findById($trackingId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'Registro de tiempo no encontrado',
                errorCode: ErrorCode::TRACKING_NOT_FOUND->value
            );
        }

        // =====================================================================
        // 2. VALIDAR QUE EL REGISTRO ESTÉ COMPLETADO
        // =====================================================================

        if ($tracking->end_time === null) {
            return ServiceResult::fail(
                message: 'No se puede aprobar un registro que aún está en progreso',
                errorCode: ErrorCode::TRACKING_NOT_STARTED->value
            );
        }

        // =====================================================================
        // 3. VALIDAR QUE NO ESTÉ YA APROBADO
        // =====================================================================

        if ($tracking->approved_at !== null) {
            return ServiceResult::fail(
                message: 'Este registro ya fue aprobado',
                errorCode: ErrorCode::TRACKING_ALREADY_APPROVED->value
            );
        }

        // =====================================================================
        // 4. VALIDAR QUE NO SEA EL MISMO USUARIO
        // =====================================================================

        if ($tracking->user_id === $approvedBy) {
            return ServiceResult::fail(
                message: 'No puedes aprobar tus propios registros',
                errorCode: ErrorCode::TRACKING_CANNOT_APPROVE_OWN->value
            );
        }

        // =====================================================================
        // 5. APROBAR REGISTRO
        // =====================================================================

        $tracking = $this->repository->approve($tracking, $approvedBy);

        // =====================================================================
        // 6. RETORNAR RESULTADO EXITOSO
        // =====================================================================

        return ServiceResult::ok(
            data: $tracking,
            message: 'Registro aprobado exitosamente'
        );
    }
}
