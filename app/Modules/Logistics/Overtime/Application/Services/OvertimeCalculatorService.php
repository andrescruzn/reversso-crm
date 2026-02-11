<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Overtime\Application\Services;

use App\Common\Services\ServiceResult;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use Carbon\Carbon;

/**
 * Servicio de cálculo de horas extras.
 *
 * RESPONSABILIDAD:
 * Calcular horas extras y recargos según legislación colombiana.
 *
 * LEGISLACIÓN COLOMBIANA:
 * - Jornada ordinaria: 47 horas semanales
 * - Nocturno (21:00-06:00): +35%
 * - Dominical/Festivo: +75%
 * - Extra diurna: +25%
 * - Extra nocturna: +75%
 * - Extra dominical diurna: +100%
 * - Extra dominical nocturna: +150%
 */
class OvertimeCalculatorService
{
    // Constantes de legislación colombiana
    private const WEEKLY_REGULAR_HOURS = 47;
    private const NIGHT_START_HOUR = 21; // 21:00
    private const NIGHT_END_HOUR = 6;    // 06:00

    // Recargos (porcentajes)
    private const SURCHARGE_NIGHT = 0.35;           // +35%
    private const SURCHARGE_HOLIDAY = 0.75;         // +75%
    private const SURCHARGE_OVERTIME_DAY = 0.25;    // +25%
    private const SURCHARGE_OVERTIME_NIGHT = 0.75;  // +75%
    private const SURCHARGE_OVERTIME_HOLIDAY_DAY = 1.00;   // +100%
    private const SURCHARGE_OVERTIME_HOLIDAY_NIGHT = 1.50; // +150%

    public function __construct(
        private readonly TimeTrackingRepositoryInterface $repository
    ) {}

    /**
     * Calcular horas extras de un usuario en un rango de fechas.
     */
    public function calculateOvertime(
        int $userId,
        string $startDate,
        string $endDate
    ): ServiceResult {
        // =====================================================================
        // 1. OBTENER REGISTROS EN EL RANGO
        // =====================================================================

        $trackings = $this->repository->getByDateRange($userId, $startDate, $endDate);

        if ($trackings->isEmpty()) {
            return ServiceResult::ok(
                data: [
                    'total_hours' => 0,
                    'regular_hours' => 0,
                    'overtime_hours' => 0,
                    'breakdown' => [],
                ],
                message: 'No hay registros en el rango de fechas'
            );
        }

        // =====================================================================
        // 2. CALCULAR HORAS POR CATEGORÍA
        // =====================================================================

        $breakdown = [
            'regular_day' => 0,
            'regular_night' => 0,
            'overtime_day' => 0,
            'overtime_night' => 0,
            'holiday_day' => 0,
            'holiday_night' => 0,
            'overtime_holiday_day' => 0,
            'overtime_holiday_night' => 0,
        ];

        $totalWeeklyHours = 0;

        foreach ($trackings as $tracking) {
            $hours = $this->categorizeHours($tracking);

            foreach ($hours as $category => $value) {
                $breakdown[$category] += $value;
            }

            $totalWeeklyHours += $tracking->getDurationInHours();
        }

        // =====================================================================
        // 3. CALCULAR RECARGOS
        // =====================================================================

        $surcharges = $this->calculateSurcharges($breakdown);

        // =====================================================================
        // 4. PREPARAR RESULTADO
        // =====================================================================

        $result = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'total_hours' => round($totalWeeklyHours, 2),
            'regular_hours' => min($totalWeeklyHours, self::WEEKLY_REGULAR_HOURS),
            'overtime_hours' => max(0, $totalWeeklyHours - self::WEEKLY_REGULAR_HOURS),
            'breakdown' => [
                'regular_day' => round($breakdown['regular_day'], 2),
                'regular_night' => round($breakdown['regular_night'], 2),
                'overtime_day' => round($breakdown['overtime_day'], 2),
                'overtime_night' => round($breakdown['overtime_night'], 2),
                'holiday_day' => round($breakdown['holiday_day'], 2),
                'holiday_night' => round($breakdown['holiday_night'], 2),
                'overtime_holiday_day' => round($breakdown['overtime_holiday_day'], 2),
                'overtime_holiday_night' => round($breakdown['overtime_holiday_night'], 2),
            ],
            'surcharges' => $surcharges,
        ];

        return ServiceResult::ok(
            data: $result,
            message: 'Cálculo de horas extras completado'
        );
    }

    /**
     * Categorizar horas de un registro según tipo.
     */
    private function categorizeHours($tracking): array
    {
        $start = Carbon::parse($tracking->start_time);
        $end = Carbon::parse($tracking->end_time);
        $isHoliday = $tracking->is_holiday;

        $hours = [
            'regular_day' => 0,
            'regular_night' => 0,
            'overtime_day' => 0,
            'overtime_night' => 0,
            'holiday_day' => 0,
            'holiday_night' => 0,
            'overtime_holiday_day' => 0,
            'overtime_holiday_night' => 0,
        ];

        // Iterar por cada hora del registro
        $current = $start->copy();
        while ($current < $end) {
            $nextHour = $current->copy()->addHour();
            if ($nextHour > $end) {
                $nextHour = $end;
            }

            $hoursInPeriod = $current->diffInHours($nextHour, true);
            $isNight = $this->isNightTime($current);

            if ($isHoliday) {
                if ($isNight) {
                    $hours['holiday_night'] += $hoursInPeriod;
                } else {
                    $hours['holiday_day'] += $hoursInPeriod;
                }
            } else {
                if ($isNight) {
                    $hours['regular_night'] += $hoursInPeriod;
                } else {
                    $hours['regular_day'] += $hoursInPeriod;
                }
            }

            $current = $nextHour;
        }

        return $hours;
    }

    /**
     * Verificar si una hora es nocturna (21:00 - 06:00).
     */
    private function isNightTime(Carbon $time): bool
    {
        $hour = $time->hour;
        return $hour >= self::NIGHT_START_HOUR || $hour < self::NIGHT_END_HOUR;
    }

    /**
     * Calcular recargos en pesos (asumiendo valor hora base).
     */
    private function calculateSurcharges(array $breakdown): array
    {
        return [
            'regular_night' => [
                'hours' => round($breakdown['regular_night'], 2),
                'percentage' => self::SURCHARGE_NIGHT * 100,
                'multiplier' => 1 + self::SURCHARGE_NIGHT,
            ],
            'holiday_day' => [
                'hours' => round($breakdown['holiday_day'], 2),
                'percentage' => self::SURCHARGE_HOLIDAY * 100,
                'multiplier' => 1 + self::SURCHARGE_HOLIDAY,
            ],
            'holiday_night' => [
                'hours' => round($breakdown['holiday_night'], 2),
                'percentage' => (self::SURCHARGE_NIGHT + self::SURCHARGE_HOLIDAY) * 100,
                'multiplier' => 1 + self::SURCHARGE_NIGHT + self::SURCHARGE_HOLIDAY,
            ],
            'overtime_day' => [
                'hours' => round($breakdown['overtime_day'], 2),
                'percentage' => self::SURCHARGE_OVERTIME_DAY * 100,
                'multiplier' => 1 + self::SURCHARGE_OVERTIME_DAY,
            ],
            'overtime_night' => [
                'hours' => round($breakdown['overtime_night'], 2),
                'percentage' => self::SURCHARGE_OVERTIME_NIGHT * 100,
                'multiplier' => 1 + self::SURCHARGE_OVERTIME_NIGHT,
            ],
            'overtime_holiday_day' => [
                'hours' => round($breakdown['overtime_holiday_day'], 2),
                'percentage' => self::SURCHARGE_OVERTIME_HOLIDAY_DAY * 100,
                'multiplier' => 1 + self::SURCHARGE_OVERTIME_HOLIDAY_DAY,
            ],
            'overtime_holiday_night' => [
                'hours' => round($breakdown['overtime_holiday_night'], 2),
                'percentage' => self::SURCHARGE_OVERTIME_HOLIDAY_NIGHT * 100,
                'multiplier' => 1 + self::SURCHARGE_OVERTIME_HOLIDAY_NIGHT,
            ],
        ];
    }
}
