<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Attendance\Domain\Contracts;

use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface
{
    /**
     * Traer asistencias cerradas (con check_out) por usuario y rango.
     *
     * @return Collection<int, object> Registros con check_in, check_out, is_holiday (y lo que aplique)
     */
    public function getClosedByUserAndDateRange(
        int $userId,
        string $startDate,
        string $endDate
    ): Collection;
}
