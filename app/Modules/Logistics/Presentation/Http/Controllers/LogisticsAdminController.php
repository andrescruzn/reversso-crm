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
            $status = $request->get('status');
            $fromDateStr = $request->get('from');
            $toDateStr = $request->get('to');

            $fromDate = $fromDateStr ? Carbon::parse($fromDateStr)->startOfDay() : Carbon::today('America/Bogota')->startOfDay();
            $toDate = $toDateStr ? Carbon::parse($toDateStr)->endOfDay() : Carbon::today('America/Bogota')->endOfDay();

            $drivers = User::whereHas('roles', fn($q) => $q->where('roles.id', 2))->orderBy('name')->get();

            // 1. CONDUCTORES EN RUTA
            $driversInRoute = $this->trackingRepository->getAllActiveWithUsers($search) ?? collect();
            $idsInRoute = $driversInRoute->pluck('user_id')->toArray();

            // 2. EN PLANTA
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

            // 3. HISTORIAL
            $query = TimeTracking::with('user')->whereNotNull('end_time')->whereBetween('end_time', [$fromDate, $toDate]);

            if ($status === 'approved') {
                $query->whereNotNull('approved_at')->where('approved_by', '!=', 0);
            } elseif ($status === 'disapproved') {
                $query->where('approved_by', 0);
            } elseif ($status === 'pending') {
                $query->whereNull('approved_at')->where('approved_by', '!=', 0);
            }

            if ($search) $query->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));

            $history = $query->orderBy('end_time', 'desc')
                ->paginate(10, ['*'], 'trips_page')
                ->appends($request->all());

            $pendingAuditCount = TimeTracking::whereNotNull('end_time')->whereNull('approved_at')->where('approved_by', '!=', 0)->count();

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
                    'from'   => $fromDate->toDateString(),
                    'to'     => $toDate->toDateString(),
                    'status' => $status
                ]
            ]);
        } catch (Exception $e) {
            dd("Error: " . $e->getMessage());
        }
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        DB::table('time_tracking')->where('id', $id)->update([
            'approved_at' => now(),
            'approved_by' => Auth::id()
        ]);

        $params = $request->only(['from', 'to', 'status', 'search', 'trips_page']);
        return redirect()->to(route('logistics.index') . '?' . http_build_query($params))
            ->with('success', 'Trayecto aprobado.');
    }

    public function disapprove(Request $request, int $id): RedirectResponse
    {
        DB::table('time_tracking')->where('id', $id)->update([
            'approved_by' => 0,
            'approved_at' => null
        ]);

        $params = $request->only(['from', 'to', 'status', 'search', 'trips_page']);
        return redirect()->to(route('logistics.index') . '?' . http_build_query($params))
            ->with('success', 'Trayecto desaprobado.');
    }

    public function exportTracking(Request $request): StreamedResponse
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $fromDate = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->startOfMonth();
        $toDate = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : now();

        $query = TimeTracking::with('user')->whereNotNull('end_time')->whereBetween('end_time', [$fromDate, $toDate]);

        if ($status === 'approved') $query->whereNotNull('approved_at')->where('approved_by', '!=', 0);
        elseif ($status === 'disapproved') $query->where('approved_by', 0);
        elseif ($status === 'pending') $query->whereNull('approved_at')->where('approved_by', '!=', 0);

        if ($search) $query->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));

        $trips = $query->orderBy('end_time', 'desc')->get();

        return response()->stream(function () use ($trips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID', 'CONDUCTOR', 'RUTA', 'DISTANCIA', 'FECHA FIN', 'ESTADO']);
            foreach ($trips as $t) {
                $dist = ($t->end_odometer && $t->start_odometer) ? ($t->end_odometer - $t->start_odometer) : 0;
                $estadoTexto = ($t->approved_by === 0) ? 'DESAPROBADO' : ($t->approved_at ? 'AUDITADO/APROBADO' : 'PENDIENTE');
                fputcsv($file, [$t->id, $t->user->name ?? 'N/A', $t->origin . ' → ' . $t->destination, $dist . ' KM', $t->end_time, $estadoTexto]);
            }
            fclose($file);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="reporte_logistica.csv"']);
    }

    public function showTrip(int $id): View
    {
        $trip = TimeTracking::with('user')->findOrFail($id);
        return view('modules.logistics.admin.trip-details', ['user' => Auth::user(), 'trip' => $trip]);
    }
}
