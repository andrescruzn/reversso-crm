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
            $status = $request->get('status'); // Filtro de Auditoría
            $fromDateStr = $request->get('from');
            $toDateStr = $request->get('to');

            $fromDate = $fromDateStr ? Carbon::parse($fromDateStr)->startOfDay() : Carbon::today('America/Bogota')->startOfDay();
            $toDate = $toDateStr ? Carbon::parse($toDateStr)->endOfDay() : Carbon::today('America/Bogota')->endOfDay();

            $drivers = User::whereHas('roles', fn($q) => $q->where('roles.id', 2))->orderBy('name')->get();

            // Query base de recorridos
            $query = TimeTracking::with('user')
                ->whereNotNull('end_time')
                ->whereBetween('end_time', [$fromDate, $toDate]);

            // Lógica de Filtro por Estado
            if ($status === 'approved') {
                $query->whereNotNull('approved_at');
            } elseif ($status === 'disapproved') {
                $query->where('approved_by', 0);
            } elseif ($status === 'pending') {
                $query->whereNull('approved_at')->where('approved_by', '!=', 0);
            }

            if ($search) {
                $query->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            }

            $history = $query->orderBy('end_time', 'desc')
                ->paginate(10, ['*'], 'trips_page')
                ->appends($request->all());

            // Datos para métricas superiores
            $driversInRoute = $this->trackingRepository->getAllActiveWithUsers($search) ?? collect();
            $pendingAuditCount = TimeTracking::whereNotNull('end_time')->whereNull('approved_at')->where('approved_by', '!=', 0)->count();

            return view('modules.logistics.admin-dashboard', [
                'user'            => Auth::user(),
                'drivers'         => $drivers,
                'driversInRoute'  => $driversInRoute,
                'completedToday'  => $history,
                'totalActive'     => $driversInRoute->count(),
                'pendingApproval' => $pendingAuditCount,
                'attendanceToday' => DB::table('user_attendance')->whereDate('check_in', today())->count(),
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

    public function approve(int $id): RedirectResponse
    {
        TimeTracking::findOrFail($id)->update([
            'approved_at' => now(),
            'approved_by' => Auth::id()
        ]);
        return redirect()->back()->with('success', 'Trayecto aprobado correctamente.');
    }

    public function disapprove(int $id): RedirectResponse
    {
        DB::table('time_tracking')->where('id', $id)->update([
            'approved_by' => 0,
            'approved_at' => null
        ]);
        return redirect()->back()->with('success', 'Trayecto desaprobado.');
    }

    public function exportTracking(Request $request): StreamedResponse
    {
        $fromDate = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->startOfMonth();
        $toDate = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : now();
        $trips = TimeTracking::with('user')->whereBetween('start_time', [$fromDate, $toDate])->orderBy('start_time', 'desc')->get();

        return response()->stream(function () use ($trips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID', 'CONDUCTOR', 'RUTA', 'KM', 'ESTADO']);
            foreach ($trips as $t) {
                $dist = ($t->end_odometer && $t->start_odometer) ? ($t->end_odometer - $t->start_odometer) : 0;
                $estado = ($t->approved_by === 0) ? 'DESAPROBADO' : ($t->approved_at ? 'APROBADO' : 'PENDIENTE');
                fputcsv($file, [$t->id, $t->user->name ?? 'N/A', $t->origin . '-' . $t->destination, $dist, $estado]);
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
