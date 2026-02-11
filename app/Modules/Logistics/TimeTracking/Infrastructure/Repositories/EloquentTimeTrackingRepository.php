<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Infrastructure\Repositories;

use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio de Time Tracking usando Eloquent.
 *
 * RESPONSABILIDAD:
 * Implementar acceso a datos de registros de tiempo.
 */
class EloquentTimeTrackingRepository implements TimeTrackingRepositoryInterface
{
    /**
     * Buscar registro por ID.
     */
    public function findById(int $id): ?TimeTracking
    {
        return TimeTracking::find($id);
    }

    /**
     * Obtener registro activo de un usuario.
     */
    public function findActiveByUserId(int $userId): ?TimeTracking
    {
        return TimeTracking::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();
    }

    /**
     * Verificar si un usuario tiene un registro activo.
     */
    public function hasActiveTracking(int $userId): bool
    {
        return TimeTracking::where('user_id', $userId)
            ->whereNull('end_time')
            ->exists();
    }

    /**
     * Crear nuevo registro de tiempo.
     */
    public function create(array $data): TimeTracking
    {
        return TimeTracking::create($data);
    }

    /**
     * Actualizar registro existente.
     */
    public function update(TimeTracking $tracking, array $data): TimeTracking
    {
        $tracking->update($data);
        return $tracking->fresh();
    }

    /**
     * Eliminar registro (soft delete).
     */
    public function delete(TimeTracking $tracking): bool
    {
        return $tracking->delete();
    }

    /**
     * Listar registros con filtros y paginación.
     */
    public function list(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = TimeTracking::with(['user', 'approver']);

        // Filtrar por usuario
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtrar por estado
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'in_progress':
                    $query->whereNull('end_time');
                    break;
                case 'completed':
                    $query->whereNotNull('end_time')
                        ->whereNull('approved_at');
                    break;
                case 'approved':
                    $query->whereNotNull('approved_at');
                    break;
            }
        }

        // Filtrar por rango de fechas
        if (isset($filters['start_date'])) {
            $query->whereDate('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('start_time', '<=', $filters['end_date']);
        }

        // Filtrar por días festivos
        if (isset($filters['is_holiday'])) {
            $query->where('is_holiday', $filters['is_holiday']);
        }

        // Ordenar por fecha más reciente
        $query->orderBy('start_time', 'desc');

        // Obtener total antes de paginar
        $total = $query->count();

        // Paginar
        $items = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Obtener historial de un usuario.
     */
    public function getUserHistory(int $userId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $filters['user_id'] = $userId;
        return $this->list($filters, $page, $limit);
    }

    /**
     * Obtener registros en rango de fechas.
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate): Collection
    {
        return TimeTracking::where('user_id', $userId)
            ->whereDate('start_time', '>=', $startDate)
            ->whereDate('start_time', '<=', $endDate)
            ->whereNotNull('end_time')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Aprobar registro.
     */
    public function approve(TimeTracking $tracking, int $approvedBy): TimeTracking
    {
        $tracking->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $tracking->fresh();
    }

    /**
     * Revertir aprobación.
     */
    public function unapprove(TimeTracking $tracking): TimeTracking
    {
        $tracking->update([
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $tracking->fresh();
    }
}
