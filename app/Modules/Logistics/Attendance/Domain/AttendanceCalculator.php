<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Attendance\Domain;

use Carbon\Carbon;

class AttendanceCalculator
{
    private const DAILY_HOURS_LIMIT = 8;

    public function calculateDetails(object $attendance): array
    {
        if (!$attendance->check_out) {
            return ['total' => 0, 'regular' => 0, 'extra' => 0];
        }

        $start = Carbon::parse($attendance->check_in);
        $end = Carbon::parse($attendance->check_out);

        $totalHours = $start->diffInMinutes($end) / 60;

        // Si es festivo, todas son extras. Si no, lo que supere el límite.
        if ($attendance->is_holiday) {
            return [
                'total' => $totalHours,
                'regular' => 0,
                'extra' => $totalHours
            ];
        }

        $regular = min($totalHours, self::DAILY_HOURS_LIMIT);
        $extra = max(0, $totalHours - self::DAILY_HOURS_LIMIT);

        return [
            'total' => $totalHours,
            'regular' => $regular,
            'extra' => $extra
        ];
    }
}
