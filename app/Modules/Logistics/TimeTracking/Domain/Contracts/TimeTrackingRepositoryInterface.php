<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Domain\Contracts;

use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato del repositorio de Time Tracking.
 *
 * RESPONSABILIDAD:
 * Definir operaciones de acceso a datos para registros de tiempo.
 */
interface TimeTrackingRepositoryInterface
{
    /**
     * Buscar registro por ID.
     */
    public function findById(int $id): ?TimeTracking;

    /**
     * Obtener registro activo de un usuario.
     */
    public function findActiveByUserId(int $userId): ?TimeTracking;

    /**
     * Verificar si un usuario tiene un registro activo.
     */
    public function hasActiveTracking(int $userId): bool;

    /**
     * Crear nuevo registro de tiempo.
     */
    public function create(array $data): TimeTracking;

    /**
     * Actualizar registro existente.
     */
    public function update(TimeTracking $tracking, array $data): TimeTracking;

    /**
     * Eliminar registro (soft delete).
     */
    public function delete(TimeTracking $tracking): bool;

    /**
     * Listar registros con filtros y paginación.
     */
    public function list(array $filters = [], int $page = 1, int $limit = 20): array;

    /**
     * Obtener historial de un usuario.
     */
    public function getUserHistory(int $userId, array $filters = [], int $page = 1, int $limit = 20): array;

    /**
     * Obtener registros en rango de fechas.
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate): Collection;

    /**
     * Aprobar registro.
     */
    public function approve(TimeTracking $tracking, int $approvedBy): TimeTracking;

    /**
     * Revertir aprobación.
     */
    public function unapprove(TimeTracking $tracking): TimeTracking;
}
