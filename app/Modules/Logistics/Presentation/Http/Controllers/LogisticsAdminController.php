<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use App\Modules\Users\Infrastructure\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogisticsAdminController extends Controller
{
    public function __construct(
        protected readonly TimeTrackingRepositoryInterface $trackingRepository
    ) {}

    public function index(Request $request): View
    {
        try {
            // ================================================================
            // 1) INPUTS DE FILTRO
            // ================================================================
            $search = $request->get('search');
            $status = $request->get('status'); // approved | disapproved | pending | null
            $fromDateStr = $request->get('from');
            $toDateStr = $request->get('to');

            // ================================================================
            // 2) RANGO DE FECHAS (por end_time)
            // ================================================================
            $fromDate = $fromDateStr
                ? Carbon::parse($fromDateStr)->startOfDay()
                : Carbon::today('America/Bogota')->startOfDay();

            $toDate = $toDateStr
                ? Carbon::parse($toDateStr)->endOfDay()
                : Carbon::today('America/Bogota')->endOfDay();

            // ================================================================
            // 3) LISTA DE CONDUCTORES ACTIVOS
            // ================================================================
            $drivers = User::whereHas('roles', fn ($q) => $q->where('name', 'conductor'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // ================================================================
            // 4) CONDUCTORES EN RUTA (activos) + IDs para excluirlos de planta
            // ================================================================
            $driversInRoute = $this->trackingRepository->getAllActiveWithUsers($search) ?? collect();
            $idsInRoute = $driversInRoute->pluck('user_id')->toArray();

            // ================================================================
            // 5) PERSONAL CON ASISTENCIA ACTIVA (NO en ruta)
            // ================================================================
            $onlyAttendance = DB::table('user_attendance')
                ->join('users', 'users.id', '=', 'user_attendance.user_id')
                ->whereDate('user_attendance.check_in', Carbon::today('America/Bogota')->toDateString())
                ->whereNull('user_attendance.check_out')
                ->whereNotIn('users.id', $idsInRoute)
                ->when($search, function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%");
                })
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'user_attendance.check_in as start_time'
                )
                ->get();

            // ================================================================
            // 5b) Separar EN PLANTA vs FUERA DE BASE
            //     Solo mira viajes terminados DESPUÉS del check-in de hoy.
            //     - Sin viajes desde check-in → "En Planta" (acaba de llegar)
            //     - Último viaje terminó en "Oficina Reversso" → "En Planta"
            //     - Último viaje terminó en otro destino → "Fuera de Base"
            // ================================================================
            $checkInTimes = $onlyAttendance->pluck('start_time', 'user_id')->toArray();
            $attendanceUserIds = array_keys($checkInTimes);

            $lastDestinations = [];
            if (!empty($attendanceUserIds)) {
                foreach ($attendanceUserIds as $uid) {
                    $dest = DB::table('time_tracking')
                        ->where('user_id', $uid)
                        ->whereNotNull('end_time')
                        ->where('end_time', '>=', $checkInTimes[$uid])
                        ->orderByDesc('end_time')
                        ->value('destination');

                    if ($dest !== null) {
                        $lastDestinations[$uid] = $dest;
                    }
                }
            }

            $inPlant = $onlyAttendance->filter(function ($person) use ($lastDestinations) {
                $dest = $lastDestinations[$person->user_id] ?? null;
                return $dest === null || mb_strtolower(trim($dest)) === 'oficina reversso';
            })->values();

            $outOfBase = $onlyAttendance->filter(function ($person) use ($lastDestinations) {
                $dest = $lastDestinations[$person->user_id] ?? null;
                return $dest !== null && mb_strtolower(trim($dest)) !== 'oficina reversso';
            })->values();

            // ================================================================
            // 6) HISTORIAL (solo cerrados) dentro del rango por end_time
            // ================================================================
            $query = TimeTracking::with('user')
                ->whereNotNull('end_time')
                ->whereBetween('end_time', [$fromDate, $toDate]);

            // ================================================================
            // 7) FILTRO POR ESTADO (TRI-ESTADO CORRECTO CON NULL)
            // - pending     => approved_by IS NULL AND approved_at IS NULL
            // - approved    => approved_by > 0 AND approved_at IS NOT NULL
            // - disapproved => approved_by = 0 AND approved_at IS NULL
            // ================================================================
            if ($status === 'approved') {
                $query->whereNotNull('approved_at')
                    ->whereNotNull('approved_by')
                    ->where('approved_by', '>', 0);
            } elseif ($status === 'disapproved') {
                $query->whereNull('approved_at')
                    ->where('approved_by', 0);
            } elseif ($status === 'pending') {
                $query->whereNull('approved_at')
                    ->whereNull('approved_by');
            }

            // ================================================================
            // 8) FILTRO POR NOMBRE DE CONDUCTOR
            // ================================================================
            if ($search) {
                $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }

            // ================================================================
            // 9) PAGINACIÓN
            // ================================================================
            $history = $query->orderBy('end_time', 'desc')
                ->paginate(10, ['*'], 'trips_page')
                ->appends($request->all());

            // ================================================================
            // 10) CONTADOR "POR AUDITAR" (pendientes reales)
            // ================================================================
            $pendingAuditCount = TimeTracking::whereNotNull('end_time')
                ->whereNull('approved_at')
                ->whereNull('approved_by')
                ->count();

            return view('modules.logistics.admin-dashboard', [
                'user'            => Auth::user(),
                'drivers'         => $drivers,
                'driversInRoute'  => $driversInRoute,
                'inPlant'         => $inPlant,
                'outOfBase'       => $outOfBase,
                'completedToday'  => $history,
                'totalActive'     => $driversInRoute->count(),
                'pendingApproval' => $pendingAuditCount,
                'attendanceToday' => $driversInRoute->count() + $inPlant->count(),
                'outOfBaseCount'  => $outOfBase->count(),
                'search'          => $search,
                'filters'         => [
                    'from'   => $fromDate->toDateString(),
                    'to'     => $toDate->toDateString(),
                    'status' => $status,
                ],
            ]);
        } catch (Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Error al cargar el dashboard: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $trip = TimeTracking::findOrFail($id);

        // ================================================================
        // VALIDACIÓN: si ya está aprobado, no lo vuelvas a aprobar
        // ================================================================
        if ($trip->approved_at !== null && $trip->approved_by !== null && (int) $trip->approved_by > 0) {
            return redirect()->to(route('logistics.index') . '?' . http_build_query(
                $request->only(['from', 'to', 'status', 'search', 'trips_page'])
            ))->with('warning', 'Este trayecto ya estaba aprobado.');
        }

        // ================================================================
        // APROBAR: approved_by = adminId, approved_at = now()
        // ================================================================
        $trip->approved_at = now();
        $trip->approved_by = (int) Auth::id();
        $trip->save();

        return redirect()->to(route('logistics.index') . '?' . http_build_query(
            $request->only(['from', 'to', 'status', 'search', 'trips_page'])
        ))->with('success', 'Trayecto aprobado.');
    }

    public function disapprove(Request $request, int $id): RedirectResponse
    {
        $trip = TimeTracking::findOrFail($id);

        // ================================================================
        // VALIDACIÓN: si ya está desaprobado, avisar
        // ================================================================
        if ($trip->approved_by !== null && (int) $trip->approved_by === 0) {
            return redirect()->to(route('logistics.index') . '?' . http_build_query(
                $request->only(['from', 'to', 'status', 'search', 'trips_page'])
            ))->with('warning', 'Este trayecto ya estaba desaprobado.');
        }

        // ================================================================
        // DESAPROBAR: approved_by = 0, approved_at = null
        // ================================================================
        $trip->approved_at = null;
        $trip->approved_by = 0;
        $trip->save();

        return redirect()->to(route('logistics.index') . '?' . http_build_query(
            $request->only(['from', 'to', 'status', 'search', 'trips_page'])
        ))->with('success', 'Trayecto desaprobado.');
    }

    public function exportTracking(Request $request): StreamedResponse
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $fromDateStr = $request->get('from');
        $toDateStr = $request->get('to');

        $fromDate = $fromDateStr
            ? Carbon::parse($fromDateStr)->startOfDay()
            : Carbon::today('America/Bogota')->startOfDay();

        $toDate = $toDateStr
            ? Carbon::parse($toDateStr)->endOfDay()
            : Carbon::today('America/Bogota')->endOfDay();

        $query = TimeTracking::with('user')
            ->whereNotNull('end_time')
            ->whereBetween('end_time', [$fromDate, $toDate]);

        // ================================================================
        // MISMA LÓGICA DE FILTROS QUE EN index()
        // ================================================================
        if ($status === 'approved') {
            $query->whereNotNull('approved_at')
                ->whereNotNull('approved_by')
                ->where('approved_by', '>', 0);
        } elseif ($status === 'disapproved') {
            $query->whereNull('approved_at')
                ->where('approved_by', 0);
        } elseif ($status === 'pending') {
            $query->whereNull('approved_at')
                ->whereNull('approved_by');
        }

        if ($search) {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
        }

        $trips = $query->orderBy('end_time', 'desc')->get();

        return response()->stream(function () use ($trips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID', 'CONDUCTOR', 'RUTA', 'DISTANCIA', 'FECHA FIN', 'ESTADO']);

            foreach ($trips as $t) {
                // Distancia
                $dist = ($t->end_odometer !== null && $t->start_odometer !== null)
                    ? ((int) $t->end_odometer - (int) $t->start_odometer)
                    : 0;

                // Estado (tri-estado)
                if ($t->approved_by !== null && (int) $t->approved_by === 0) {
                    $estadoTexto = 'DESAPROBADO';
                } elseif ($t->approved_by !== null && (int) $t->approved_by > 0 && $t->approved_at !== null) {
                    $estadoTexto = 'APROBADO';
                } else {
                    $estadoTexto = 'PENDIENTE';
                }

                fputcsv($file, [
                    $t->id,
                    $t->user->name ?? 'N/A',
                    trim(($t->origin ?? '') . ' - ' . ($t->destination ?? '')),
                    $dist . ' KM',
                    $t->end_time,
                    $estadoTexto,
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reporte_logistica.csv"',
        ]);
    }

    public function showTrip(int $id): View
    {
        $trip = TimeTracking::with('user')->findOrFail($id);

        return view('modules.logistics.admin.trip-details', [
            'user' => Auth::user(),
            'trip' => $trip,
        ]);
    }
}
