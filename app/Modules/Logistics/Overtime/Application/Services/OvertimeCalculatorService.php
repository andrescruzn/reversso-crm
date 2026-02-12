<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Overtime\Application\Services;

use App\Common\Services\ServiceResult;
use App\Modules\Logistics\Attendance\Domain\Contracts\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de cálculo de horas extras y recargos (Colombia).
 *
 * PATRÓN:
 * - Application Service (orquesta cálculo)
 * - Repository Pattern (trae data de asistencia)
 *
 * REGLA DE NEGOCIO:
 * - Clasificar minutos trabajados como:
 *   - Diurno / Nocturno
 *   - Normal / Dominical-Festivo
 *   - Ordinario / Extra (por exceder el límite semanal vigente)
 *
 * NOTA:
 * - "Nocturno" NO es "extra" automáticamente. Es recargo por franja horaria.
 * - "Extra" se define por exceder el límite semanal (44/42 según fecha).
 */
final class OvertimeCalculatorService
{
    private const MINUTES_PER_HOUR = 60;

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
        // =====================================================================
        // 1) Traer asistencias cerradas del rango
        // =====================================================================
        $attendances = $this->attendanceRepository
            ->getClosedByUserAndDateRange($userId, $startDate, $endDate);

        if ($attendances->isEmpty()) {
            return ServiceResult::ok(
                data: $this->emptyResult($startDate, $endDate),
                message: 'No hay asistencias cerradas en el rango'
            );
        }

        // =====================================================================
        // 2) Construir "minutos trabajados" por semana ISO, preservando flags
        // =====================================================================
        $minutesByIsoWeek = $this->buildMinutesByIsoWeek($attendances);

        // =====================================================================
        // 3) Clasificar minutos como ordinarios vs extra por límite semanal
        // =====================================================================
        $breakdownMinutes = $this->classifyMinutesWithWeeklyLimit($minutesByIsoWeek);

        // =====================================================================
        // 4) Convertir a horas y calcular recargos/multiplicadores (presentación cruda)
        // =====================================================================
        $breakdownHours = $this->minutesBreakdownToHours($breakdownMinutes);
        $surcharges = $this->calculateSurcharges($breakdownHours);

        // =====================================================================
        // 5) Totales
        // =====================================================================
        $totalHours = $this->sumBreakdownHours($breakdownHours);

        return ServiceResult::ok(
            data: [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'total_hours' => round($totalHours, 2),
                'breakdown' => $breakdownHours,
                'surcharges' => $surcharges,
            ],
            message: 'Cálculo Colombia completado'
        );
    }

    // ======================================================================
    // Helpers de dominio del cálculo (privados)
    // ======================================================================

    private function emptyResult(string $startDate, string $endDate): array
    {
        return [
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'total_hours' => 0,
            'breakdown' => [
                'regular_day' => 0,
                'regular_night' => 0,
                'holiday_day' => 0,
                'holiday_night' => 0,
                'overtime_day' => 0,
                'overtime_night' => 0,
                'overtime_holiday_day' => 0,
                'overtime_holiday_night' => 0,
            ],
            'surcharges' => [],
        ];
    }

    /**
     * Construir minutos por semana ISO:
     * - Key: "YYYY-Www" (ej: 2026-W07)
     * - Value: lista de minutos (fecha-hora) con flags night/holiday
     *
     * Importante:
     * - Iteramos por minuto para exactitud (sin truncar parciales).
     */
    private function buildMinutesByIsoWeek(Collection $attendances): array
    {
        $minutesByWeek = [];

        foreach ($attendances as $attendance) {
            $start = Carbon::parse($attendance->check_in);
            $end = Carbon::parse($attendance->check_out);

            // Seguridad: si hay datos raros, saltar
            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            // Iteración minuto a minuto (precisa)
            $current = $start->copy();

            while ($current->lessThan($end)) {
                $weekKey = $current->isoFormat('GGGG-[W]WW');

                $minutesByWeek[$weekKey] ??= [];
                $minutesByWeek[$weekKey][] = [
                    'dt' => $current->copy(),
                    'is_night' => $this->isNightTime($current),
                    // Si tu attendance ya trae is_holiday, lo respetamos.
                    // Si no, al menos detectamos domingo (mejora mínima).
                    'is_holiday' => $this->isHolidayMinute($current, (bool) ($attendance->is_holiday ?? false)),
                ];

                $current->addMinute();
            }
        }

        return $minutesByWeek;
    }

    /**
     * Aplicar el límite semanal vigente:
     * - Recorremos minutos en orden cronológico por semana.
     * - Los primeros N minutos (límite semanal) son ordinarios.
     * - El resto son extra.
     *
     * Mantiene:
     * - night/day
     * - holiday/normal
     */
    private function classifyMinutesWithWeeklyLimit(array $minutesByIsoWeek): array
    {
        $result = [
            'regular_day' => 0,
            'regular_night' => 0,
            'holiday_day' => 0,
            'holiday_night' => 0,
            'overtime_day' => 0,
            'overtime_night' => 0,
            'overtime_holiday_day' => 0,
            'overtime_holiday_night' => 0,
        ];

        foreach ($minutesByIsoWeek as $weekKey => $minutesList) {
            // Orden cronológico por seguridad
            usort($minutesList, fn ($a, $b) => $a['dt']->getTimestamp() <=> $b['dt']->getTimestamp());

            $weekStart = $minutesList[0]['dt']->copy()->startOfWeek(Carbon::MONDAY);
            $weeklyLimitMinutes = $this->getWeeklyLimitMinutesForDate($weekStart);

            $minuteIndex = 0;

            foreach ($minutesList as $minute) {
                $isOvertime = $minuteIndex >= $weeklyLimitMinutes;

                $bucket = $this->resolveBucket(
                    isHoliday: (bool) $minute['is_holiday'],
                    isNight: (bool) $minute['is_night'],
                    isOvertime: $isOvertime
                );

                $result[$bucket] += 1; // 1 minuto
                $minuteIndex++;
            }
        }

        return $result;
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

    /**
     * Nocturno: 19:00 - 06:00 (desde 2025-12-25)
     */
    private function isNightTime(Carbon $time): bool
    {
        $nightConfig = config('overtime.night');
        $effectiveFrom = Carbon::parse((string) $nightConfig['effective_from']);

        // Si la fecha es anterior a la vigencia, usa el esquema anterior (21:00) por compatibilidad.
        $startHour = $time->greaterThanOrEqualTo($effectiveFrom)
            ? (int) $nightConfig['start_hour']
            : 21;

        $endHour = (int) $nightConfig['end_hour'];

        $hour = (int) $time->hour;

        return $hour >= $startHour || $hour < $endHour;
    }

    /**
     * Festivo/dominical:
     * - Si el registro ya venía marcado como festivo, lo respetamos.
     * - Si no, mínimo detectamos domingo por calendario.
     *
     * Mejora posterior (recomendada):
     * - Cruzar con tabla calendar_day_off / festivos legales.
     */
    private function isHolidayMinute(Carbon $time, bool $explicitHolidayFlag): bool
    {
        if ($explicitHolidayFlag) {
            return true;
        }

        // Domingo
        return $time->isSunday();
    }

    private function getWeeklyLimitMinutesForDate(Carbon $dateInWeek): int
    {
        $weeklyLimits = (array) config('overtime.weekly_limits');

        foreach ($weeklyLimits as $rule) {
            $from = Carbon::parse((string) $rule['from'])->startOfDay();
            $to = isset($rule['to']) && $rule['to']
                ? Carbon::parse((string) $rule['to'])->endOfDay()
                : null;

            if ($dateInWeek->betweenIncluded($from, $to ?? $dateInWeek->copy()->addYears(50))) {
                return ((int) $rule['hours']) * self::MINUTES_PER_HOUR;
            }
        }

        // Fallback conservador: 44h
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
        return array_sum(array_map(
            fn ($v) => (float) $v,
            $breakdownHours
        ));
    }

    /**
     * Calcula “multiplicadores” por categoría (sin pesos).
     * La liquidación en pesos debe hacerse en nómina con valor hora base.
     */
    private function calculateSurcharges(array $breakdownHours): array
    {
        $night = (float) config('overtime.night.surcharge');

        $holidaySurcharge = $this->resolveHolidaySurchargeForNow();
        $ot = (array) config('overtime.overtime_surcharges');

        return [
            // Recargos ordinarios
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

            // Horas extra
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

    /**
     * Recargo dominical/festivo según escalones (progresivo).
     * Elegimos el último escalón aplicable para "hoy".
     */
    private function resolveHolidaySurchargeForNow(): float
    {
        $steps = (array) config('overtime.holiday_surcharge_steps');
        $today = Carbon::now()->startOfDay();

        $value = 0.75; // fallback histórico

        foreach ($steps as $step) {
            $from = Carbon::parse((string) $step['from'])->startOfDay();
            if ($today->greaterThanOrEqualTo($from)) {
                $value = (float) $step['value'];
            }
        }

        return $value;
    }

    public function calculateOvertimeDailyBreakdown(
        int $userId,
        string $startDate,
        string $endDate
    ): ServiceResult {
        $attendances = $this->attendanceRepository
            ->getClosedByUserAndDateRange($userId, $startDate, $endDate);

        if ($attendances->isEmpty()) {
            return ServiceResult::ok(
                data: [
                    'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                    'daily' => [],
                    'totals' => $this->emptyResult($startDate, $endDate),
                ],
                message: 'No hay asistencias cerradas en el rango'
            );
        }

        $minutesByIsoWeek = $this->buildMinutesByIsoWeek($attendances);

        // 👇 NUEVO: clasifica y devuelve también por día
        [$totalsMinutes, $dailyMinutes] = $this->classifyMinutesWithWeeklyLimitDaily($minutesByIsoWeek);

        $totalsHours = $this->minutesBreakdownToHours($totalsMinutes);

        $dailyHours = [];
        foreach ($dailyMinutes as $day => $breakdownMinutes) {
            $dailyHours[$day] = $this->minutesBreakdownToHours($breakdownMinutes);
            $dailyHours[$day]['total_hours'] = $this->sumBreakdownHours($dailyHours[$day]);
        }

        return ServiceResult::ok(
            data: [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'daily' => $dailyHours,
                'totals' => [
                    'total_hours' => round($this->sumBreakdownHours($totalsHours), 2),
                    'breakdown' => $totalsHours,
                ],
            ],
            message: 'Cálculo diario Colombia completado'
        );
    }

    private function classifyMinutesWithWeeklyLimitDaily(array $minutesByIsoWeek): array
    {
        $totals = [
            'regular_day' => 0,
            'regular_night' => 0,
            'holiday_day' => 0,
            'holiday_night' => 0,
            'overtime_day' => 0,
            'overtime_night' => 0,
            'overtime_holiday_day' => 0,
            'overtime_holiday_night' => 0,
        ];

        $daily = [];

        foreach ($minutesByIsoWeek as $minutesList) {
            usort($minutesList, fn ($a, $b) => $a['dt']->getTimestamp() <=> $b['dt']->getTimestamp());

            $weekStart = $minutesList[0]['dt']->copy()->startOfWeek(Carbon::MONDAY);
            $weeklyLimitMinutes = $this->getWeeklyLimitMinutesForDate($weekStart);

            $minuteIndex = 0;

            foreach ($minutesList as $minute) {
                $isOvertime = $minuteIndex >= $weeklyLimitMinutes;

                $bucket = $this->resolveBucket(
                    isHoliday: (bool) $minute['is_holiday'],
                    isNight: (bool) $minute['is_night'],
                    isOvertime: $isOvertime
                );

                $totals[$bucket] += 1;

                $dayKey = $minute['dt']->toDateString();
                $daily[$dayKey] ??= [
                    'regular_day' => 0,
                    'regular_night' => 0,
                    'holiday_day' => 0,
                    'holiday_night' => 0,
                    'overtime_day' => 0,
                    'overtime_night' => 0,
                    'overtime_holiday_day' => 0,
                    'overtime_holiday_night' => 0,
                ];
                $daily[$dayKey][$bucket] += 1;

                $minuteIndex++;
            }
        }

        return [$totals, $daily];
    }

}
