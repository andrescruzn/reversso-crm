<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\TimeTracking\Infrastructure\Persistence\EloquentTimeTrackingRepository;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Auth, DB};
use Exception;

class LogisticsAdminController extends Controller
{
    protected $trackingRepository;

    public function __construct(EloquentTimeTrackingRepository $trackingRepository)
    {
        $this->trackingRepository = $trackingRepository;
    }

    /**
     * DASHBOARD PRINCIPAL (Supervisión Logística)
     */
    public function index(Request $request): View
    {
        try {
            $search = $request->get('search');

            // 1. Manejo de fechas para el HISTORIAL (Tabla inferior)
            // Si no hay filtro, por defecto mostramos HOY.
            $fromDateStr = $request->get('from');
            $toDateStr = $request->get('to');

            $fromDate = $fromDateStr ? Carbon::parse($fromDateStr)->startOfDay() : Carbon::today('America/Bogota')->startOfDay();
            $toDate = $toDateStr ? Carbon::parse($toDateStr)->endOfDay() : Carbon::today('America/Bogota')->endOfDay();

            // 2. CONDUCTORES EN RUTA (En vivo - No se filtran por fecha histórica)
            $driversInRoute = $this->trackingRepository->getAllActiveWithUsers($search) ?? collect();
            $idsInRoute = $driversInRoute->pluck('user_id')->toArray();

            // 3. CONDUCTORES EN PLANTA (Asistencia activa hoy sin viaje iniciado)
            $onlyAttendance = DB::table('user_attendance')
                ->join('users', 'users.id', '=', 'user_attendance.user_id')
                ->join('role_user', 'users.id', '=', 'role_user.user_id')
                ->where('role_user.role_id', 2)
                ->whereDate('user_attendance.check_in', Carbon::today('America/Bogota')->toDateString())
                ->whereNull('user_attendance.check_out')
                ->whereNull('user_attendance.deleted_at')
                ->whereNotIn('users.id', $idsInRoute)
                ->when($search, function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%");
                })
                ->select('users.id as user_id', 'users.name as user_name', 'user_attendance.check_in as start_time')
                ->get();

            // 4. Viajes esperando aprobación
            $pendingTrips = $this->trackingRepository->getPendingApprovals() ?? collect();

            // 5. RECORRIDOS FINALIZADOS (Historial dinámico por fecha)
            $completedHistory = TimeTracking::with('user')
                ->whereNotNull('end_time')
                ->whereBetween('end_time', [$fromDate, $toDate])
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
                })
                ->orderBy('end_time', 'desc')
                ->paginate(10, ['*'], 'trips_page')
                ->appends($request->all());

            return view('modules.logistics.admin-dashboard', [
                'user'            => Auth::user(),
                'driversInRoute'  => $driversInRoute,
                'onlyAttendance'  => $onlyAttendance,
                'pendingTrips'    => $pendingTrips,
                'completedToday'  => $completedHistory,
                'totalActive'     => $driversInRoute->count(),
                'pendingApproval' => $pendingTrips->count(),
                'attendanceToday' => $driversInRoute->count() + $onlyAttendance->count(),
                'search'          => $search,
                'filters'         => [
                    'from' => $fromDate->toDateString(),
                    'to'   => $toDate->toDateString()
                ]
            ]);
        } catch (Exception $e) {
            dd("Error en Logistics Dashboard: " . $e->getMessage());
        }
    }

    public function exportTracking(Request $request): StreamedResponse
    {
        $fromDate = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->startOfMonth();
        $toDate = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : now();

        $trips = TimeTracking::with('user')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->stream(function () use ($trips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID VIAJE', 'CONDUCTOR', 'ORIGEN', 'DESTINO', 'FECHA INICIO', 'HORA INICIO', 'FECHA FIN', 'HORA FIN', 'KM INICIAL', 'KM FINAL', 'DISTANCIA TOTAL', 'ESTADO APROBACIÓN']);
            foreach ($trips as $t) {
                $start = Carbon::parse($t->start_time);
                $end = $t->end_time ? Carbon::parse($t->end_time) : null;
                $distancia = ($t->end_odometer && $t->start_odometer) ? ($t->end_odometer - $t->start_odometer) : 0;
                fputcsv($file, [$t->id, $t->user->name ?? 'N/A', $t->origin, $t->destination, $start->format('d/m/Y'), $start->format('H:i:s'), $end ? $end->format('d/m/Y') : 'EN RUTA', $end ? $end->format('H:i:s') : '---', $t->start_odometer, $t->end_odometer ?? '---', $distancia . ' km', $t->approved_at ? 'APROBADO' : 'PENDIENTE']);
            }
            fclose($file);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="tracking_reversso_' . now()->format('dmY_His') . '.csv"']);
    }

    public function showTrip(int $id): View
    {
        $trip = $this->trackingRepository->findById($id);
        if (!$trip) abort(404);
        return view('modules.logistics.admin.trip-details', ['user' => Auth::user(), 'trip' => $trip]);
    }

    public function attendanceReport(Request $request): View
    {
        $userId = $request->get('user_id');
        $fromDate = $request->get('from') ? Carbon::parse($request->get('from')) : now()->startOfMonth();
        $toDate = $request->get('to') ? Carbon::parse($request->get('to')) : now();
        $drivers = User::whereHas('roles', function ($q) {
            $q->where('roles.id', 2);
        })->orderBy('name')->get();
        $query = DB::table('user_attendance')->join('users', 'users.id', '=', 'user_attendance.user_id')->select('user_attendance.*', 'users.name as user_name')->whereNull('user_attendance.deleted_at')->whereBetween('user_attendance.check_in', [$fromDate->startOfDay(), $toDate->endOfDay()]);
        if ($userId) $query->where('user_attendance.user_id', $userId);
        $reportData = $query->orderBy('check_in', 'asc')->get()->groupBy(fn($item) => $item->user_id . '_' . Carbon::parse($item->check_in)->toDateString())->map(function ($group) {
            $first = $group->first();
            $totalMin = $group->reduce(fn($c, $i) => ($i->check_in && $i->check_out) ? $c + Carbon::parse($i->check_in)->diffInMinutes(Carbon::parse($i->check_out)) : $c, 0);
            return (object)['user_name' => $first->user_name, 'date' => Carbon::parse($first->check_in), 'is_holiday' => (bool)$first->is_holiday, 'total_day' => (float)($totalMin / 60)];
        })->values()->sortByDesc('date');
        return view('modules.logistics.admin.attendance-report', ['user' => Auth::user(), 'drivers' => $drivers, 'attendances' => $reportData, 'filters' => ['user_id' => $userId, 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]]);
    }
}
