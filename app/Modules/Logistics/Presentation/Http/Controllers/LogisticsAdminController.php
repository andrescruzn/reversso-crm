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
use Illuminate\Http\RedirectResponse;

class LogisticsAdminController extends Controller
{
    protected $trackingRepository;

    public function __construct(EloquentTimeTrackingRepository $trackingRepository)
    {
        $this->trackingRepository = $trackingRepository;
    }

    public function index(Request $request): View
    {
        try {
            $search = $request->get('search');
            $fromDateStr = $request->get('from');
            $toDateStr = $request->get('to');

            // Filtro de fecha (Default: Hoy - Bogotá)
            $fromDate = $fromDateStr ? Carbon::parse($fromDateStr)->startOfDay() : Carbon::today('America/Bogota')->startOfDay();
            $toDate = $toDateStr ? Carbon::parse($toDateStr)->endOfDay() : Carbon::today('America/Bogota')->endOfDay();

            $drivers = User::whereHas('roles', fn($q) => $q->where('roles.id', 2))->orderBy('name')->get();

            // 1. ESTADO EN VIVO (Independiente del filtro de historial)
            $driversInRoute = $this->trackingRepository->getAllActiveWithUsers($search) ?? collect();
            $idsInRoute = $driversInRoute->pluck('user_id')->toArray();

            $onlyAttendance = DB::table('user_attendance')
                ->join('users', 'users.id', '=', 'user_attendance.user_id')
                ->whereDate('user_attendance.check_in', Carbon::today('America/Bogota')->toDateString())
                ->whereNull('user_attendance.check_out')
                ->whereNotIn('users.id', $idsInRoute)
                ->when($search, function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%");
                })
                ->select('users.id as user_id', 'users.name as user_name', 'user_attendance.check_in as start_time')
                ->get();

            // 2. MÉTRICAS (Basadas en estado global de auditoría)
            $pendingAuditCount = TimeTracking::whereNotNull('end_time')->whereNull('approved_at')->count();

            // 3. TABLA UNIFICADA DE RECORRIDOS (Filtrada por fecha y búsqueda)
            $history = TimeTracking::with('user')
                ->whereNotNull('end_time')
                ->whereBetween('end_time', [$fromDate, $toDate])
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('end_time', 'desc')
                ->paginate(10, ['*'], 'trips_page')
                ->appends($request->all());

            return view('modules.logistics.admin-dashboard', [
                'user'            => Auth::user(),
                'drivers'         => $drivers,
                'driversInRoute'  => $driversInRoute,
                'onlyAttendance'  => $onlyAttendance,
                'completedToday'  => $history,
                'totalActive'     => $driversInRoute->count(),
                'pendingApproval' => $pendingAuditCount,
                'attendanceToday' => $driversInRoute->count() + $onlyAttendance->count(),
                'search'          => $search,
                'filters'         => [
                    'from' => $fromDate->toDateString(),
                    'to'   => $toDate->toDateString()
                ]
            ]);
        } catch (Exception $e) {
            dd("Error: " . $e->getMessage());
        }
    }

    // MÉTODO PARA REVERTIR APROBACIÓN
    public function disapprove(int $id): RedirectResponse
    {
        TimeTracking::findOrFail($id)->update(['approved_at' => null]);
        return redirect()->back()->with('success', 'El trayecto regresó a estado pendiente.');
    }

    public function exportTracking(Request $request): StreamedResponse
    {
        $fromDate = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->startOfMonth();
        $toDate = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : now();
        $trips = TimeTracking::with('user')->whereBetween('start_time', [$fromDate, $toDate])->orderBy('start_time', 'desc')->get();

        return response()->stream(function () use ($trips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID', 'CONDUCTOR', 'RUTA', 'DISTANCIA', 'ESTADO']);
            foreach ($trips as $t) {
                $dist = ($t->end_odometer && $t->start_odometer) ? ($t->end_odometer - $t->start_odometer) : 0;
                fputcsv($file, [$t->id, $t->user->name ?? 'N/A', $t->origin . '-' . $t->destination, $dist, $t->approved_at ? 'APROBADO' : 'PENDIENTE']);
            }
            fclose($file);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="tracking.csv"']);
    }

    public function showTrip(int $id): View
    {
        $trip = TimeTracking::with('user')->findOrFail($id);
        return view('modules.logistics.admin.trip-details', ['user' => Auth::user(), 'trip' => $trip]);
    }
}
