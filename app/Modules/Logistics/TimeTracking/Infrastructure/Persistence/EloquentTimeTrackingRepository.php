<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Infrastructure\Persistence;

use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Illuminate\Support\Collection;

class EloquentTimeTrackingRepository
{
    public function findActiveByUser(int $userId)
    {
        return \Illuminate\Support\Facades\DB::table('time_tracking')
            ->where('user_id', $userId)
            ->whereNull('end_time') // O whereNull('end_odometer') según tu estructura
            ->first();
    }

    /**
     * Obtiene conductores activos con filtro opcional de búsqueda.
     */
    public function getAllActiveWithUsers(?string $search = null): Collection
    {
        $query = TimeTracking::with('user')->whereNull('end_time');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('start_time', 'desc')->get();
    }

    /**
     * Obtiene viajes finalizados que aún no han sido aprobados por el admin.
     */
    public function getPendingApprovals(): Collection
    {
        return TimeTracking::with('user')
            ->whereNotNull('end_time')
            ->whereNull('approved_at')
            ->orderBy('end_time', 'desc')
            ->get();
    }

    public function findById(int $id): ?TimeTracking
    {
        return TimeTracking::with('user')->find($id);
    }

    public function getHistoryByUser(int $userId, string $startDate, string $endDate): Collection
    {
        return TimeTracking::where('user_id', $userId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('end_time')
            ->orderBy('start_time', 'desc')
            ->get();
    }
}
