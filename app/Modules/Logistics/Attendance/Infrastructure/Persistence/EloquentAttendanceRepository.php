<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Attendance\Infrastructure\Persistence;

use App\Modules\Logistics\Attendance\Domain\Contracts\AttendanceRepositoryInterface;
use App\Modules\Logistics\Attendance\Infrastructure\Models\UserAttendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

final class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function getClosedByUserAndDateRange(
        int $userId,
        string $startDate,
        string $endDate
    ): Collection {
        return UserAttendance::query()
            ->where('user_id', $userId)
            ->whereNotNull('check_out') // solo jornadas cerradas
            ->whereBetween('check_in', [
                Carbon::parse($startDate)->startOfDay()->toDateTimeString(),
                Carbon::parse($endDate)->endOfDay()->toDateTimeString(),
            ])
            ->orderBy('check_in', 'asc')
            ->get();
    }
}
