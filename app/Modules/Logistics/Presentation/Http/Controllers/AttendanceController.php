<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Attendance\Infrastructure\Models\UserAttendance;
use App\Modules\Logistics\Overtime\Application\Services\OvertimeCalculatorService;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttendanceController extends Controller
{
    /**
     * Repositorio de tracking (viajes) para validaciones al cerrar jornada.
     */
    protected TimeTrackingRepositoryInterface $timeTrackingRepo;

    /**
     * Servicio de cálculo Colombia (nocturno, dominical/festivo, extra real, etc.).
     */
    private readonly OvertimeCalculatorService $overtimeService;

    public function __construct(
        TimeTrackingRepositoryInterface $timeTrackingRepo,
        OvertimeCalculatorService $overtimeService
    ) {
        $this->timeTrackingRepo = $timeTrackingRepo;
        $this->overtimeService = $overtimeService;
    }

    /**
     * Muestra el reporte de asistencia (Admin).
     */
    public function index(Request $request): View
    {
        // =====================================================================
        // 1) Filtros + conductores
        // =====================================================================
        $filters = $this->parseFilters($request);

        $drivers = User::whereHas('roles', fn ($q) => $q->where('name', 'conductor'))
            ->where('is_active', true)
            ->get();

        // Query base usando Eloquent para consistencia
        $query = $this->getRawAttendanceQuery($filters);

        // =====================================================================
        // 2) Lógica de grilla con paginación
        // =====================================================================
        if (!$filters['user_id']) {
            // -----------------------------------------------------------------
            // VISTA RESUMEN (TODOS): una fila por conductor
            // -----------------------------------------------------------------
            $rawRecords = $query->get();

            $data = $rawRecords->groupBy('user_id')->map(function ($group) use ($filters) {
                $first = $group->first();

                // =============================================================
                // Cálculo Colombia por usuario y rango (una sola llamada)
                // =============================================================
                $result = $this->overtimeService->calculateOvertime(
                    (int) $first->user_id,
                    (string) $filters['from'],
                    (string) $filters['to']
                );

                $payload = is_array($result->data) ? $result->data : [];
                $breakdown = isset($payload['breakdown']) && is_array($payload['breakdown'])
                    ? $payload['breakdown']
                    : [];

                $totalHours = (float) ($payload['total_hours'] ?? 0);

                // Ordinarias (día + noche ordinaria)
                $regularHours = (float) (
                    ($breakdown['regular_day'] ?? 0) +
                    ($breakdown['regular_night'] ?? 0)
                );

                // Dominical/festivo ordinario
                $holidayHours = (float) (
                    ($breakdown['holiday_day'] ?? 0) +
                    ($breakdown['holiday_night'] ?? 0)
                );

                // Dominical/festivo pero además "extra" (por exceder límite semanal)
                $overtimeHolidayHours = (float) (
                    ($breakdown['overtime_holiday_day'] ?? 0) +
                    ($breakdown['overtime_holiday_night'] ?? 0)
                );

                // Extra real (incluye extra normal y extra dominical/festivo)
                $overtimeHours = (float) (
                    ($breakdown['overtime_day'] ?? 0) +
                    ($breakdown['overtime_night'] ?? 0) +
                    $overtimeHolidayHours
                );

                // UI actual: extras/recargos = dominical/festivo + overtime real
                $extraForUi = $holidayHours + $overtimeHours;

                // ✅ Aunque en resumen no muestres badge, dejamos flag correcto
                $isHolidayEffective = ($holidayHours + $overtimeHolidayHours) > 0;

                return (object) [
                    'user_id'       => (int) $first->user_id,
                    'user_name'     => (string) $first->user_name,
                    'date'          => null,
                    'check_in'      => null,
                    'check_out'     => null,
                    'is_holiday'    => false,
                    'is_summary'    => true,

                    // ✅ Dominical/festivo detectado por cálculo (incluye overtime_holiday)
                    'is_holiday_effective' => $isHolidayEffective,

                    'hours_regular' => $this->formatHoursToTime($regularHours),
                    'hours_extra'   => $this->formatHoursToTime($extraForUi),
                    'total_day'     => $this->formatHoursToTime($totalHours),

                    // opcionales
                    'regular_day'    => $this->formatHoursToTime((float) ($breakdown['regular_day'] ?? 0)),
                    'regular_night'  => $this->formatHoursToTime((float) ($breakdown['regular_night'] ?? 0)),
                    'holiday_day'    => $this->formatHoursToTime((float) ($breakdown['holiday_day'] ?? 0)),
                    'holiday_night'  => $this->formatHoursToTime((float) ($breakdown['holiday_night'] ?? 0)),
                    'overtime_total' => $this->formatHoursToTime($overtimeHours),
                ];
            })->values()->sortBy('user_name');

            // Paginación manual del resumen
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 15;

            $currentPageItems = $data->slice(($currentPage - 1) * $perPage, $perPage)->all();

            $attendances = new LengthAwarePaginator($currentPageItems, count($data), $perPage);
            $attendances->setPath($request->url())->appends($request->all());
        } else {
            // -----------------------------------------------------------------
            // VISTA DETALLE (UN USUARIO): detalle por día (paginado)
            // -----------------------------------------------------------------

            // ✅ 1) Paginamos los registros (solo afecta lo que se ve)
            $rawPaginator = $query->orderBy('check_in', 'desc')->paginate(15)->appends($request->all());

            // ✅ 2) El cálculo Colombia debe hacerse con rango COMPLETO
            //     para que "extra real semanal" cuadre.
            $dailyResult = $this->overtimeService->calculateOvertimeDailyBreakdown(
                (int) $filters['user_id'],
                (string) $filters['from'],
                (string) $filters['to']
            );

            $dailyPayload = is_array($dailyResult->data) ? $dailyResult->data : [];
            $dailyMap = (array) ($dailyPayload['daily'] ?? []);

            $transformedItems = collect($rawPaginator->items())->map(function ($item) use ($dailyMap) {
                $day = Carbon::parse($item->check_in)->toDateString();

                // Breakdown por día ya con contexto semanal
                $breakdown = (isset($dailyMap[$day]) && is_array($dailyMap[$day]))
                    ? $dailyMap[$day]
                    : [];

                $totalHours = (float) ($breakdown['total_hours'] ?? 0);

                $regularHours = (float) (
                    ($breakdown['regular_day'] ?? 0) +
                    ($breakdown['regular_night'] ?? 0)
                );

                $holidayHours = (float) (
                    ($breakdown['holiday_day'] ?? 0) +
                    ($breakdown['holiday_night'] ?? 0)
                );

                $overtimeHolidayHours = (float) (
                    ($breakdown['overtime_holiday_day'] ?? 0) +
                    ($breakdown['overtime_holiday_night'] ?? 0)
                );

                $overtimeHours = (float) (
                    ($breakdown['overtime_day'] ?? 0) +
                    ($breakdown['overtime_night'] ?? 0) +
                    $overtimeHolidayHours
                );

                $extraForUi = $holidayHours + $overtimeHours;

                // ✅ Badge “real”:
                // - si hubo cualquier minuto dominical/festivo (normal u overtime_holiday)
                // - o si en DB quedó marcado is_holiday manualmente
                $isHolidayEffective = (($holidayHours + $overtimeHolidayHours) > 0) || ((bool) $item->is_holiday);

                return (object) [
                    'user_id'       => (int) $item->user_id,
                    'user_name'     => (string) $item->user_name,
                    'date'          => Carbon::parse($item->check_in),
                    'check_in'      => Carbon::parse($item->check_in)->format('H:i'),
                    'check_out'     => $item->check_out ? Carbon::parse($item->check_out)->format('H:i') : 'PTE',
                    'is_holiday'    => (bool) $item->is_holiday, // flag manual DB
                    'is_summary'    => false,

                    'is_holiday_effective' => $isHolidayEffective,

                    'hours_regular' => $this->formatHoursToTime($regularHours),
                    'hours_extra'   => $this->formatHoursToTime($extraForUi),
                    'total_day'     => $this->formatHoursToTime($totalHours),

                    // opcionales
                    'regular_day'    => $this->formatHoursToTime((float) ($breakdown['regular_day'] ?? 0)),
                    'regular_night'  => $this->formatHoursToTime((float) ($breakdown['regular_night'] ?? 0)),
                    'holiday_day'    => $this->formatHoursToTime((float) ($breakdown['holiday_day'] ?? 0)),
                    'holiday_night'  => $this->formatHoursToTime((float) ($breakdown['holiday_night'] ?? 0)),
                    'overtime_total' => $this->formatHoursToTime($overtimeHours),
                ];
            });

            $attendances = new LengthAwarePaginator(
                $transformedItems,
                $rawPaginator->total(),
                $rawPaginator->perPage(),
                $rawPaginator->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('modules.logistics.admin.attendance-report', [
            'drivers'     => $drivers,
            'attendances' => $attendances,
            'filters'     => $filters,
        ]);
    }

    /**
     * Exportar reporte a CSV con desglose de horas extras/recargos.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);

        $query = UserAttendance::query()
            ->join('users', 'users.id', '=', 'user_attendance.user_id')
            ->select([
                'user_attendance.user_id',
                'user_attendance.check_in',
                'user_attendance.check_out',
                'user_attendance.is_holiday',
                'users.name as user_name',
            ])
            ->whereBetween('user_attendance.check_in', [
                Carbon::parse($filters['from'])->startOfDay()->toDateTimeString(),
                Carbon::parse($filters['to'])->endOfDay()->toDateTimeString(),
            ]);

        if (!empty($filters['user_id'])) {
            $query->where('user_attendance.user_id', (int) $filters['user_id']);
        }

        $query->orderBy('users.name', 'asc')->orderBy('user_attendance.check_in', 'asc');

        // Pre-calcular overtime por usuario usando el servicio Colombia
        $records = $query->get();
        $userIds = $records->pluck('user_id')->unique();

        $dailyMaps = [];
        foreach ($userIds as $userId) {
            $result = $this->overtimeService->calculateOvertimeDailyBreakdown(
                (int) $userId,
                (string) $filters['from'],
                (string) $filters['to']
            );
            $payload = is_array($result->data) ? $result->data : [];
            $dailyMaps[$userId] = (array) ($payload['daily'] ?? []);
        }

        $fileName = 'asistencia_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($records, $dailyMaps) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'CONDUCTOR',
                'FECHA',
                'ENTRADA',
                'SALIDA',
                'H. REGULARES',
                'H. EXTRAS / RECARGOS',
                'TIEMPO TOTAL',
                'TIPO',
            ]);

            foreach ($records as $row) {
                $checkIn = Carbon::parse($row->check_in);
                $checkOut = $row->check_out ? Carbon::parse($row->check_out) : null;
                $day = $checkIn->toDateString();

                $breakdown = $dailyMaps[$row->user_id][$day] ?? [];

                $regularHours = (float) (($breakdown['regular_day'] ?? 0) + ($breakdown['regular_night'] ?? 0));

                $holidayHours = (float) (($breakdown['holiday_day'] ?? 0) + ($breakdown['holiday_night'] ?? 0));
                $overtimeHolidayHours = (float) (($breakdown['overtime_holiday_day'] ?? 0) + ($breakdown['overtime_holiday_night'] ?? 0));
                $overtimeHours = (float) (($breakdown['overtime_day'] ?? 0) + ($breakdown['overtime_night'] ?? 0) + $overtimeHolidayHours);
                $extraForUi = $holidayHours + $overtimeHours;

                $totalHours = (float) ($breakdown['total_hours'] ?? 0);

                $isHoliday = (($holidayHours + $overtimeHolidayHours) > 0) || ((bool) $row->is_holiday);

                $tipo = 'NORMAL';
                if ($isHoliday && $overtimeHours > 0) {
                    $tipo = 'FESTIVO + EXTRA';
                } elseif ($isHoliday) {
                    $tipo = 'FESTIVO';
                } elseif ($overtimeHours > 0) {
                    $tipo = 'EXTRA';
                }

                fputcsv($file, [
                    $row->user_name,
                    $checkIn->toDateString(),
                    $checkIn->format('H:i'),
                    $checkOut ? $checkOut->format('H:i') : 'PTE',
                    $this->formatHoursToTime($regularHours),
                    $this->formatHoursToTime($extraForUi),
                    $this->formatHoursToTime($totalHours),
                    $tipo,
                ]);

                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                if (function_exists('flush')) {
                    @flush();
                }
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();

        if ($this->getActiveAttendance($userId)) {
            return redirect()->back()->with('error', 'Ya tienes una jornada activa.');
        }

        UserAttendance::create([
            'user_id'    => $userId,
            'check_in'   => now(),
            'is_holiday' => $request->has('is_holiday'),
            'status'     => 'active',
        ]);

        OvertimeCalculatorService::clearCacheForUser($userId);

        return redirect()->back()->with('success', 'Jornada iniciada.');
    }

    public function checkOut(): RedirectResponse
    {
        $userId = (int) Auth::id();
        $attendance = $this->getActiveAttendance($userId);

        if (!$attendance) {
            return redirect()->back()->with('error', 'No hay jornada activa para cerrar.');
        }

        $activeTrip = $this->timeTrackingRepo->findActiveByUserId($userId);

        if ($activeTrip) {
            $tripId = $activeTrip->tracking_number ?? $activeTrip->id;

            return redirect()->back()->with(
                'error',
                "⛔ ALERTA: No puedes cerrar jornada. Tienes el viaje #{$tripId} en curso. Finalízalo primero."
            );
        }

        $attendance->update([
            'check_out' => now(),
            'status'    => 'completed',
        ]);

        OvertimeCalculatorService::clearCacheForUser($userId);

        return redirect()->route('dashboard')->with('success', 'Jornada finalizada correctamente.');
    }

    // ======================================================================
    // Helpers Privados
    // ======================================================================

    private function getActiveAttendance(int $userId)
    {
        return UserAttendance::where('user_id', $userId)
            ->whereNull('check_out')
            ->first();
    }

    private function getRawAttendanceQuery(array $filters)
    {
        $query = UserAttendance::query()
            ->join('users', 'users.id', '=', 'user_attendance.user_id')
            ->select('user_attendance.*', 'users.name as user_name')
            ->whereBetween('user_attendance.check_in', [
                Carbon::parse($filters['from'])->startOfDay()->toDateTimeString(),
                Carbon::parse($filters['to'])->endOfDay()->toDateTimeString(),
            ]);

        if (!empty($filters['user_id'])) {
            $query->where('user_attendance.user_id', $filters['user_id']);
        }

        return $query;
    }

    private function formatHoursToTime(float $hoursDecimal): string
    {
        $totalMinutes = (int) round($hoursDecimal * 60);
        return sprintf('%dh %02dm', (int) floor($totalMinutes / 60), $totalMinutes % 60);
    }

    private function parseFilters(Request $request): array
    {
        return [
            'user_id' => $request->get('user_id'),
            'from'    => $request->get('from') ?? now()->startOfMonth()->toDateString(),
            'to'      => $request->get('to') ?? now()->toDateString(),
        ];
    }
}
