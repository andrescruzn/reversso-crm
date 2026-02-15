<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Overtime\Application\Services;

use App\Common\Services\ServiceResult;
use App\Modules\Logistics\Attendance\Domain\Contracts\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de cálculo de horas extras y recargos (Colombia).
 *
 * REGLA DE NEGOCIO:
 * - Clasificar minutos trabajados como:
 *   - Diurno / Nocturno
 *   - Normal / Dominical-Festivo
 *   - Ordinario / Extra (por exceder el límite semanal vigente)
 *
 * OPTIMIZACIÓN:
 * - Usa segmentos (día/noche/medianoche) en lugar de iteración minuto-a-minuto.
 * - Cachea resultados por usuario+rango para evitar recálculos.
 */
final class OvertimeCalculatorService
{
    private const MINUTES_PER_HOUR = 60;

    /** Configuración cacheada en memoria para la request actual */
    private ?array $nightConfig = null;
    private ?array $weeklyLimits = null;

    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository
    ) {}

    /**
     * Calcular horas y recargos de un usuario en un rango.
     */
    public function calculateOvertime(
        int $userId,
        string $startDate,
        string $endDate
    ): ServiceResult {
        $cacheKey = "overtime:{$userId}:{$startDate}:{$endDate}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $startDate, $endDate) {
            $attendances = $this->attendanceRepository
                ->getClosedByUserAndDateRange($userId, $startDate, $endDate);

            if ($attendances->isEmpty()) {
                return $this->emptyResult($startDate, $endDate);
            }

            $segmentsByWeek = $this->buildSegmentsByIsoWeek($attendances);
            $breakdownMinutes = $this->classifySegmentsWithWeeklyLimit($segmentsByWeek);
            $breakdownHours = $this->minutesBreakdownToHours($breakdownMinutes);
            $surcharges = $this->calculateSurcharges($breakdownHours);
            $totalHours = $this->sumBreakdownHours($breakdownHours);

            return [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'total_hours' => round($totalHours, 2),
                'breakdown' => $breakdownHours,
                'surcharges' => $surcharges,
            ];
        });

        return ServiceResult::ok(data: $data, message: 'Cálculo Colombia completado');
    }

    /**
     * Calcular con desglose diario.
     */
    public function calculateOvertimeDailyBreakdown(
        int $userId,
        string $startDate,
        string $endDate
    ): ServiceResult {
        $cacheKey = "overtime_daily:{$userId}:{$startDate}:{$endDate}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $startDate, $endDate) {
            $attendances = $this->attendanceRepository
                ->getClosedByUserAndDateRange($userId, $startDate, $endDate);

            if ($attendances->isEmpty()) {
                return [
                    'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                    'daily' => [],
                    'totals' => $this->emptyResult($startDate, $endDate),
                ];
            }

            $segmentsByWeek = $this->buildSegmentsByIsoWeek($attendances);
            [$totalsMinutes, $dailyMinutes] = $this->classifySegmentsWithWeeklyLimitDaily($segmentsByWeek);

            $totalsHours = $this->minutesBreakdownToHours($totalsMinutes);

            $dailyHours = [];
            foreach ($dailyMinutes as $day => $breakdownMinutes) {
                $dailyHours[$day] = $this->minutesBreakdownToHours($breakdownMinutes);
                $dailyHours[$day]['total_hours'] = $this->sumBreakdownHours($dailyHours[$day]);
            }

            return [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'daily' => $dailyHours,
                'totals' => [
                    'total_hours' => round($this->sumBreakdownHours($totalsHours), 2),
                    'breakdown' => $totalsHours,
                ],
            ];
        });

        return ServiceResult::ok(data: $data, message: 'Cálculo diario Colombia completado');
    }

    // ======================================================================
    // Construcción de segmentos (reemplazo de minuto-a-minuto)
    // ======================================================================

    /**
     * Divide cada jornada en segmentos por fronteras (medianoche, día/noche).
     * En lugar de 480 iteraciones por 8h, genera máximo 3-4 segmentos.
     *
     * Cada segmento: [weekKey, dayKey, minutes, is_night, is_holiday]
     */
    private function buildSegmentsByIsoWeek(Collection $attendances): array
    {
        $nightCfg = $this->getNightConfig();
        $segmentsByWeek = [];

        foreach ($attendances as $attendance) {
            $start = Carbon::parse($attendance->check_in);
            $end = Carbon::parse($attendance->check_out);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $isHolidayFlag = (bool) ($attendance->is_holiday ?? false);

            // Dividir la jornada en sub-intervalos por fronteras
            $cursor = $start->copy();

            while ($cursor->lessThan($end)) {
                // Calcular la siguiente frontera
                $nextBoundary = $this->getNextBoundary($cursor, $end, $nightCfg);
                $minutes = (int) $cursor->diffInMinutes($nextBoundary);

                if ($minutes <= 0) {
                    $cursor = $nextBoundary->copy();
                    continue;
                }

                $weekKey = $cursor->isoFormat('GGGG-[W]WW');
                $dayKey = $cursor->toDateString();
                $isNight = $this->isNightHour($cursor->hour, $cursor, $nightCfg);
                $isHoliday = $isHolidayFlag || $cursor->isSunday();

                $segmentsByWeek[$weekKey] ??= [];
                $segmentsByWeek[$weekKey][] = [
                    'day' => $dayKey,
                    'minutes' => $minutes,
                    'is_night' => $isNight,
                    'is_holiday' => $isHoliday,
                    'timestamp' => $cursor->getTimestamp(),
                ];

                $cursor = $nextBoundary->copy();
            }
        }

        return $segmentsByWeek;
    }

    /**
     * Calcula la siguiente frontera temporal (medianoche, inicio/fin nocturno, o fin de jornada).
     */
    private function getNextBoundary(Carbon $cursor, Carbon $end, array $nightCfg): Carbon
    {
        $boundaries = [];

        // Medianoche siguiente (cambio de día)
        $midnight = $cursor->copy()->addDay()->startOfDay();
        if ($midnight->lessThan($end)) {
            $boundaries[] = $midnight;
        }

        $startHour = $this->getEffectiveNightStartHour($cursor, $nightCfg);
        $endHour = (int) $nightCfg['end_hour'];

        // Frontera inicio nocturno (ej: 19:00)
        $nightStart = $cursor->copy()->startOfDay()->addHours($startHour);
        if ($nightStart->greaterThan($cursor) && $nightStart->lessThan($end)) {
            $boundaries[] = $nightStart;
        }

        // Frontera fin nocturno (ej: 06:00)
        $nightEnd = $cursor->copy()->startOfDay()->addHours($endHour);
        if ($nightEnd->greaterThan($cursor) && $nightEnd->lessThan($end)) {
            $boundaries[] = $nightEnd;
        }

        // El fin de jornada siempre es candidato
        $boundaries[] = $end;

        // Retornar la frontera más cercana
        usort($boundaries, fn (Carbon $a, Carbon $b) => $a->getTimestamp() <=> $b->getTimestamp());

        return $boundaries[0];
    }

    // ======================================================================
    // Clasificación con límite semanal (por segmentos)
    // ======================================================================

    /**
     * Clasifica segmentos como ordinarios vs extra por límite semanal.
     * Versión totales (sin desglose diario).
     */
    private function classifySegmentsWithWeeklyLimit(array $segmentsByWeek): array
    {
        $result = $this->emptyBreakdown();

        foreach ($segmentsByWeek as $segments) {
            usort($segments, fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

            $weekStart = Carbon::createFromTimestamp($segments[0]['timestamp'])->startOfWeek(Carbon::MONDAY);
            $weeklyLimitMinutes = $this->getWeeklyLimitMinutesForDate($weekStart);

            $minuteIndex = 0;

            foreach ($segments as $seg) {
                $segMinutes = $seg['minutes'];

                // Parte que cae dentro del límite semanal
                $regularMinutes = 0;
                $overtimeMinutes = 0;

                if ($minuteIndex >= $weeklyLimitMinutes) {
                    $overtimeMinutes = $segMinutes;
                } elseif ($minuteIndex + $segMinutes <= $weeklyLimitMinutes) {
                    $regularMinutes = $segMinutes;
                } else {
                    $regularMinutes = $weeklyLimitMinutes - $minuteIndex;
                    $overtimeMinutes = $segMinutes - $regularMinutes;
                }

                if ($regularMinutes > 0) {
                    $bucket = $this->resolveBucket($seg['is_holiday'], $seg['is_night'], false);
                    $result[$bucket] += $regularMinutes;
                }

                if ($overtimeMinutes > 0) {
                    $bucket = $this->resolveBucket($seg['is_holiday'], $seg['is_night'], true);
                    $result[$bucket] += $overtimeMinutes;
                }

                $minuteIndex += $segMinutes;
            }
        }

        return $result;
    }

    /**
     * Clasifica segmentos con desglose diario + totales.
     */
    private function classifySegmentsWithWeeklyLimitDaily(array $segmentsByWeek): array
    {
        $totals = $this->emptyBreakdown();
        $daily = [];

        foreach ($segmentsByWeek as $segments) {
            usort($segments, fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

            $weekStart = Carbon::createFromTimestamp($segments[0]['timestamp'])->startOfWeek(Carbon::MONDAY);
            $weeklyLimitMinutes = $this->getWeeklyLimitMinutesForDate($weekStart);

            $minuteIndex = 0;

            foreach ($segments as $seg) {
                $segMinutes = $seg['minutes'];
                $dayKey = $seg['day'];

                $regularMinutes = 0;
                $overtimeMinutes = 0;

                if ($minuteIndex >= $weeklyLimitMinutes) {
                    $overtimeMinutes = $segMinutes;
                } elseif ($minuteIndex + $segMinutes <= $weeklyLimitMinutes) {
                    $regularMinutes = $segMinutes;
                } else {
                    $regularMinutes = $weeklyLimitMinutes - $minuteIndex;
                    $overtimeMinutes = $segMinutes - $regularMinutes;
                }

                $daily[$dayKey] ??= $this->emptyBreakdown();

                if ($regularMinutes > 0) {
                    $bucket = $this->resolveBucket($seg['is_holiday'], $seg['is_night'], false);
                    $totals[$bucket] += $regularMinutes;
                    $daily[$dayKey][$bucket] += $regularMinutes;
                }

                if ($overtimeMinutes > 0) {
                    $bucket = $this->resolveBucket($seg['is_holiday'], $seg['is_night'], true);
                    $totals[$bucket] += $overtimeMinutes;
                    $daily[$dayKey][$bucket] += $overtimeMinutes;
                }

                $minuteIndex += $segMinutes;
            }
        }

        return [$totals, $daily];
    }

    // ======================================================================
    // Helpers
    // ======================================================================

    private function emptyBreakdown(): array
    {
        return [
            'regular_day' => 0,
            'regular_night' => 0,
            'holiday_day' => 0,
            'holiday_night' => 0,
            'overtime_day' => 0,
            'overtime_night' => 0,
            'overtime_holiday_day' => 0,
            'overtime_holiday_night' => 0,
        ];
    }

    private function emptyResult(string $startDate, string $endDate): array
    {
        return [
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'total_hours' => 0,
            'breakdown' => $this->emptyBreakdown(),
            'surcharges' => [],
        ];
    }

    private function resolveBucket(bool $isHoliday, bool $isNight, bool $isOvertime): string
    {
        if ($isHoliday) {
            if ($isOvertime) {
                return $isNight ? 'overtime_holiday_night' : 'overtime_holiday_day';
            }
            return $isNight ? 'holiday_night' : 'holiday_day';
        }

        if ($isOvertime) {
            return $isNight ? 'overtime_night' : 'overtime_day';
        }

        return $isNight ? 'regular_night' : 'regular_day';
    }

    private function getNightConfig(): array
    {
        if ($this->nightConfig === null) {
            $this->nightConfig = (array) config('overtime.night');
        }
        return $this->nightConfig;
    }

    private function getEffectiveNightStartHour(Carbon $date, array $nightCfg): int
    {
        $effectiveFrom = Carbon::parse((string) $nightCfg['effective_from']);
        return $date->greaterThanOrEqualTo($effectiveFrom)
            ? (int) $nightCfg['start_hour']
            : 21;
    }

    private function isNightHour(int $hour, Carbon $date, array $nightCfg): bool
    {
        $startHour = $this->getEffectiveNightStartHour($date, $nightCfg);
        $endHour = (int) $nightCfg['end_hour'];
        return $hour >= $startHour || $hour < $endHour;
    }

    private function getWeeklyLimitMinutesForDate(Carbon $dateInWeek): int
    {
        if ($this->weeklyLimits === null) {
            $this->weeklyLimits = (array) config('overtime.weekly_limits');
        }

        foreach ($this->weeklyLimits as $rule) {
            $from = Carbon::parse((string) $rule['from'])->startOfDay();
            $to = isset($rule['to']) && $rule['to']
                ? Carbon::parse((string) $rule['to'])->endOfDay()
                : null;

            if ($dateInWeek->betweenIncluded($from, $to ?? $dateInWeek->copy()->addYears(50))) {
                return ((int) $rule['hours']) * self::MINUTES_PER_HOUR;
            }
        }

        return 44 * self::MINUTES_PER_HOUR;
    }

    private function minutesBreakdownToHours(array $breakdownMinutes): array
    {
        $toHours = fn (int $m) => round($m / self::MINUTES_PER_HOUR, 4);

        return [
            'regular_day' => $toHours((int) $breakdownMinutes['regular_day']),
            'regular_night' => $toHours((int) $breakdownMinutes['regular_night']),
            'holiday_day' => $toHours((int) $breakdownMinutes['holiday_day']),
            'holiday_night' => $toHours((int) $breakdownMinutes['holiday_night']),
            'overtime_day' => $toHours((int) $breakdownMinutes['overtime_day']),
            'overtime_night' => $toHours((int) $breakdownMinutes['overtime_night']),
            'overtime_holiday_day' => $toHours((int) $breakdownMinutes['overtime_holiday_day']),
            'overtime_holiday_night' => $toHours((int) $breakdownMinutes['overtime_holiday_night']),
        ];
    }

    private function sumBreakdownHours(array $breakdownHours): float
    {
        return array_sum(array_map(fn ($v) => (float) $v, $breakdownHours));
    }

    private function calculateSurcharges(array $breakdownHours): array
    {
        $nightCfg = $this->getNightConfig();
        $night = (float) $nightCfg['surcharge'];
        $holidaySurcharge = $this->resolveHolidaySurchargeForNow();
        $ot = (array) config('overtime.overtime_surcharges');

        return [
            'regular_night' => [
                'hours' => round($breakdownHours['regular_night'], 2),
                'percentage' => $night * 100,
                'multiplier' => 1 + $night,
            ],
            'holiday_day' => [
                'hours' => round($breakdownHours['holiday_day'], 2),
                'percentage' => $holidaySurcharge * 100,
                'multiplier' => 1 + $holidaySurcharge,
            ],
            'holiday_night' => [
                'hours' => round($breakdownHours['holiday_night'], 2),
                'percentage' => ($holidaySurcharge + $night) * 100,
                'multiplier' => 1 + $holidaySurcharge + $night,
            ],
            'overtime_day' => [
                'hours' => round($breakdownHours['overtime_day'], 2),
                'percentage' => ((float) $ot['day']) * 100,
                'multiplier' => 1 + (float) $ot['day'],
            ],
            'overtime_night' => [
                'hours' => round($breakdownHours['overtime_night'], 2),
                'percentage' => ((float) $ot['night']) * 100,
                'multiplier' => 1 + (float) $ot['night'],
            ],
            'overtime_holiday_day' => [
                'hours' => round($breakdownHours['overtime_holiday_day'], 2),
                'percentage' => ((float) $ot['holiday_day']) * 100,
                'multiplier' => 1 + (float) $ot['holiday_day'],
            ],
            'overtime_holiday_night' => [
                'hours' => round($breakdownHours['overtime_holiday_night'], 2),
                'percentage' => ((float) $ot['holiday_night']) * 100,
                'multiplier' => 1 + (float) $ot['holiday_night'],
            ],
        ];
    }

    private function resolveHolidaySurchargeForNow(): float
    {
        $steps = (array) config('overtime.holiday_surcharge_steps');
        $today = Carbon::now()->startOfDay();
        $value = 0.75;

        foreach ($steps as $step) {
            $from = Carbon::parse((string) $step['from'])->startOfDay();
            if ($today->greaterThanOrEqualTo($from)) {
                $value = (float) $step['value'];
            }
        }

        return $value;
    }

    /**
     * Invalidar caché de un usuario (llamar al hacer check-in/check-out).
     */
    public static function clearCacheForUser(int $userId): void
    {
        // Limpia patrón conocido: mes actual y anterior
        $now = Carbon::now();
        $ranges = [
            [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()],
            [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
        ];

        foreach ($ranges as [$start, $end]) {
            Cache::forget("overtime:{$userId}:{$start}:{$end}");
            Cache::forget("overtime_daily:{$userId}:{$start}:{$end}");
        }
    }
}
