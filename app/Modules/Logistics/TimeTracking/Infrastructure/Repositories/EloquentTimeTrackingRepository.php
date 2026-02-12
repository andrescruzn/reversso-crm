<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Infrastructure\Repositories;

use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio de Time Tracking usando Eloquent.
 */
class EloquentTimeTrackingRepository implements TimeTrackingRepositoryInterface
{
    public function findById(int $id): ?TimeTracking
    {
        return TimeTracking::find($id);
    }

    /**
     * Obtener registro activo (IN_PROGRESS) de un usuario.
     * Se asume activo si 'end_time' es nulo.
     */
    public function findActiveByUserId(int $userId): ?TimeTracking
    {
        return TimeTracking::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();
    }

    public function hasActiveTracking(int $userId): bool
    {
        return TimeTracking::where('user_id', $userId)
            ->whereNull('end_time')
            ->exists();
    }

    public function create(array $data): TimeTracking
    {
        return TimeTracking::create($data);
    }

    public function update(TimeTracking $tracking, array $data): TimeTracking
    {
        $tracking->update($data);
        return $tracking->fresh();
    }

    public function delete(TimeTracking $tracking): bool
    {
        return $tracking->delete();
    }

    public function list(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = TimeTracking::with(['user']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Lógica de estado si se envía filtro
        if (isset($filters['status'])) {
            if ($filters['status'] === 'in_progress') {
                $query->whereNull('end_time');
            } elseif ($filters['status'] === 'completed') {
                $query->whereNotNull('end_time');
            }
        }

        $query->orderBy('start_time', 'desc');

        $total = $query->count();
        $items = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getUserHistory(int $userId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $filters['user_id'] = $userId;
        return $this->list($filters, $page, $limit);
    }

    public function getByDateRange(int $userId, string $startDate, string $endDate): Collection
    {
        return TimeTracking::where('user_id', $userId)
            ->whereDate('start_time', '>=', $startDate)
            ->whereDate('start_time', '<=', $endDate)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    public function approve(TimeTracking $tracking, int $approvedBy): TimeTracking
    {
        $tracking->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $tracking->fresh();
    }

    public function unapprove(TimeTracking $tracking): TimeTracking
    {
        $tracking->update([
            'approved_by' => null,
            'approved_at' => null,
        ]);
        return $tracking->fresh();
    }
}
