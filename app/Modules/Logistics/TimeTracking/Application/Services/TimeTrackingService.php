<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Application\Services;

use App\Common\Enums\ErrorCode;
use App\Common\Services\ServiceResult;
use App\Modules\Logistics\TimeTracking\Application\Actions\ApproveTrackingAction;
use App\Modules\Logistics\TimeTracking\Application\Actions\EndTrackingAction;
use App\Modules\Logistics\TimeTracking\Application\Actions\StartTrackingAction;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;

/**
 * Servicio de Time Tracking.
 *
 * PATRÓN: Application Service
 *
 * RESPONSABILIDAD:
 * Orquestar operaciones de gestión de registros de tiempo.
 */
class TimeTrackingService
{
    public function __construct(
        private readonly TimeTrackingRepositoryInterface $repository,
        private readonly StartTrackingAction $startAction,
        private readonly EndTrackingAction $endAction,
        private readonly ApproveTrackingAction $approveAction
    ) {}

    // =====================================================================
    // OPERACIONES DE CONDUCTORES
    // =====================================================================

    /**
     * Iniciar jornada de trabajo.
     */
    public function startTracking(
        int $userId,
        string $origin,
        ?float $startOdometer = null,
        bool $isHoliday = false,
        ?string $observations = null
    ): ServiceResult {
        return $this->startAction->execute(
            $userId,
            $origin,
            $startOdometer,
            $isHoliday,
            $observations
        );
    }

    /**
     * Finalizar jornada de trabajo.
     */
    public function endTracking(
        int $userId,
        string $destination,
        ?float $endOdometer = null,
        ?string $observations = null
    ): ServiceResult {
        return $this->endAction->execute(
            $userId,
            $destination,
            $endOdometer,
            $observations
        );
    }

    /**
     * Obtener registro activo del usuario.
     */
    public function getActiveTracking(int $userId): ServiceResult
    {
        $tracking = $this->repository->findActiveByUserId($userId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'No tienes un registro de tiempo activo',
                errorCode: ErrorCode::TRACKING_NOT_STARTED->value
            );
        }

        return ServiceResult::ok(
            data: $tracking,
            message: 'Registro activo obtenido'
        );
    }

    /**
     * Obtener historial del usuario.
     */
    public function getUserHistory(
        int $userId,
        array $filters = [],
        int $page = 1,
        int $limit = 20
    ): ServiceResult {
        $result = $this->repository->getUserHistory($userId, $filters, $page, $limit);

        return ServiceResult::ok(
            data: $result,
            message: 'Historial obtenido exitosamente'
        );
    }

    // =====================================================================
    // OPERACIONES DE ADMINISTRADORES
    // =====================================================================

    /**
     * Listar todos los registros (con filtros).
     */
    public function listAll(
        array $filters = [],
        int $page = 1,
        int $limit = 20
    ): ServiceResult {
        $result = $this->repository->list($filters, $page, $limit);

        return ServiceResult::ok(
            data: $result,
            message: 'Registros obtenidos exitosamente'
        );
    }

    /**
     * Obtener registro específico por ID.
     */
    public function getById(int $trackingId): ServiceResult
    {
        $tracking = $this->repository->findById($trackingId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'Registro de tiempo no encontrado',
                errorCode: ErrorCode::TRACKING_NOT_FOUND->value
            );
        }

        return ServiceResult::ok(
            data: $tracking,
            message: 'Registro obtenido exitosamente'
        );
    }

    /**
     * Aprobar registro.
     */
    public function approve(int $trackingId, int $approvedBy): ServiceResult
    {
        return $this->approveAction->execute($trackingId, $approvedBy);
    }

    /**
     * Revertir aprobación.
     */
    public function unapprove(int $trackingId): ServiceResult
    {
        $tracking = $this->repository->findById($trackingId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'Registro de tiempo no encontrado',
                errorCode: ErrorCode::TRACKING_NOT_FOUND->value
            );
        }

        if ($tracking->approved_at === null) {
            return ServiceResult::fail(
                message: 'Este registro no está aprobado',
                errorCode: ErrorCode::GENERAL_ERROR->value
            );
        }

        $tracking = $this->repository->unapprove($tracking);

        return ServiceResult::ok(
            data: $tracking,
            message: 'Aprobación revertida exitosamente'
        );
    }

    /**
     * Actualizar registro (edición administrativa).
     */
    public function update(int $trackingId, array $data): ServiceResult
    {
        $tracking = $this->repository->findById($trackingId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'Registro de tiempo no encontrado',
                errorCode: ErrorCode::TRACKING_NOT_FOUND->value
            );
        }

        $tracking = $this->repository->update($tracking, $data);

        return ServiceResult::ok(
            data: $tracking,
            message: 'Registro actualizado exitosamente'
        );
    }

    /**
     * Eliminar registro.
     */
    public function delete(int $trackingId): ServiceResult
    {
        $tracking = $this->repository->findById($trackingId);

        if (!$tracking) {
            return ServiceResult::fail(
                message: 'Registro de tiempo no encontrado',
                errorCode: ErrorCode::TRACKING_NOT_FOUND->value
            );
        }

        $this->repository->delete($tracking);

        return ServiceResult::ok(
            data: null,
            message: 'Registro eliminado exitosamente'
        );
    }
}
