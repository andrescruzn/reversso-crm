<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->parseFilters($request);
        $drivers = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'admin'))->get();

        // 1. Obtenemos la query base
        $query = $this->getRawAttendanceQuery($filters);

        /**
         * LÓGICA DE GRILLA (PANTALLA) CON PAGINACIÓN
         */
        if (!$filters['user_id']) {
            // TODOS: Agrupamos y calculamos totales (Aquí la paginación suele ser corta por número de empleados)
            $rawRecords = $query->get();
            $data = $rawRecords->groupBy('user_id')->map(function ($group) {
                $first = $group->first();
                $totals = $this->calculateTotals($group);
                return (object) [
                    'user_id'       => $first->user_id,
                    'user_name'     => $first->user_name,
                    'date'          => null,
                    'check_in'      => null,
                    'check_out'     => null,
                    'is_holiday'    => false,
                    'is_summary'    => true,
                    'hours_regular' => $this->formatHoursToTime($totals['regular']),
                    'hours_extra'   => $this->formatHoursToTime($totals['extra']),
                    'total_day'     => $this->formatHoursToTime($totals['total']),
                ];
            })->values()->sortBy('user_name');

            // Convertimos a paginador manual para mantener consistencia
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 15;
            $currentPageItems = $data->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $attendances = new LengthAwarePaginator($currentPageItems, count($data), $perPage);
            $attendances->setPath($request->url())->appends($request->all());
        } else {
            // UN USUARIO: Detalle diario paginado (Los 2 meses de data)
            $rawPaginator = $query->orderBy('check_in', 'desc')->paginate(15)->appends($request->all());

            // Transformamos los items manteniendo el objeto paginador
            $transformedItems = collect($rawPaginator->items())->map(function ($item) {
                $totals = $this->calculateTotals(collect([$item]));
                return (object) [
                    'user_id'       => $item->user_id,
                    'user_name'     => $item->user_name,
                    'date'          => Carbon::parse($item->check_in),
                    'check_in'      => Carbon::parse($item->check_in)->format('H:i'),
                    'check_out'     => $item->check_out ? Carbon::parse($item->check_out)->format('H:i') : 'PTE',
                    'is_holiday'    => (bool)$item->is_holiday,
                    'is_summary'    => false,
                    'hours_regular' => $this->formatHoursToTime($totals['regular']),
                    'hours_extra'   => $this->formatHoursToTime($totals['extra']),
                    'total_day'     => $this->formatHoursToTime($totals['total']),
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
            'filters'     => $filters
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $records = $this->getRawAttendanceQuery($filters)->orderBy('check_in', 'asc')->get();
        $fileName = "asistencia_" . now()->format('Ymd_His') . ".csv";

        return response()->stream(function () use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['CONDUCTOR', 'FECHA', 'ENTRADA', 'SALIDA', 'TIEMPO TOTAL', 'TIPO']);

            foreach ($records as $row) {
                $checkIn = Carbon::parse($row->check_in);
                $checkOut = $row->check_out ? Carbon::parse($row->check_out) : null;
                $totalMin = $checkOut ? $checkIn->diffInMinutes($checkOut) : 0;
                fputcsv($file, [
                    $row->user_name,
                    $checkIn->toDateString(),
                    $checkIn->format('H:i'),
                    $checkOut ? $checkOut->format('H:i') : 'PTE',
                    $this->formatHoursToTime($totalMin / 60),
                    $row->is_holiday ? 'FESTIVO' : 'NORMAL'
                ]);
            }
            fclose($file);
        }, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
        ]);
    }

    private function calculateTotals($group): array
    {
        $reg = 0;
        $ext = 0;
        $tot = 0;
        foreach ($group as $item) {
            if ($item->check_in && $item->check_out) {
                $hours = Carbon::parse($item->check_in)->diffInMinutes(Carbon::parse($item->check_out)) / 60;
                $tot += $hours;
                if ((bool)$item->is_holiday) {
                    $ext += $hours;
                } else {
                    $reg += min($hours, 8);
                    $ext += max(0, $hours - 8);
                }
            }
        }
        return ['regular' => $reg, 'extra' => $ext, 'total' => $tot];
    }

    private function formatHoursToTime(float $hoursDecimal): string
    {
        $totalMinutes = (int) round($hoursDecimal * 60);
        return sprintf('%dh %02dm', floor($totalMinutes / 60), $totalMinutes % 60);
    }

    private function getRawAttendanceQuery(array $filters)
    {
        $query = DB::table('user_attendance')
            ->join('users', 'users.id', '=', 'user_attendance.user_id')
            ->select('user_attendance.*', 'users.name as user_name')
            ->whereNull('user_attendance.deleted_at')
            ->whereBetween('user_attendance.check_in', [
                Carbon::parse($filters['from'])->startOfDay()->toDateTimeString(),
                Carbon::parse($filters['to'])->endOfDay()->toDateTimeString()
            ]);

        if (!empty($filters['user_id'])) {
            $query->where('user_attendance.user_id', $filters['user_id']);
        }
        return $query;
    }

    private function parseFilters(Request $request): array
    {
        return [
            'user_id' => $request->get('user_id'),
            'from'    => $request->get('from') ?? now()->startOfMonth()->toDateString(),
            'to'      => $request->get('to') ?? now()->toDateString(),
        ];
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        if ($this->getActiveAttendance((int)$userId)) return redirect()->back()->with('error', 'Ya tienes una jornada activa.');
        DB::table('user_attendance')->insert(['user_id' => $userId, 'check_in' => now(), 'is_holiday' => $request->has('is_holiday') ? 1 : 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Jornada iniciada.');
    }

    public function checkOut(): RedirectResponse
    {
        $userId = Auth::id();
        $attendance = $this->getActiveAttendance((int)$userId);
        if (!$attendance) return redirect()->back()->with('error', 'No hay jornada activa.');
        DB::table('user_attendance')->where('id', $attendance->id)->update(['check_out' => now(), 'status' => 'completed', 'updated_at' => now()]);
        return redirect()->route('dashboard')->with('success', 'Jornada finalizada.');
    }

    private function getActiveAttendance(int $userId)
    {
        return DB::table('user_attendance')->where('user_id', $userId)->whereNull('check_out')->whereNull('deleted_at')->first();
    }
}
