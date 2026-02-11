<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Overtime\Application\Services\OvertimeCalculatorService;
use App\Modules\Logistics\TimeTracking\Infrastructure\Persistence\EloquentTimeTrackingRepository;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OvertimeCalculatorService $overtimeService,
        private readonly EloquentTimeTrackingRepository $trackingRepository
    ) {}

    /**
     * Módulo de Logística con soporte de filtros por rango de fecha
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Capturar parámetros de fecha. Si no vienen, usamos hoy.
        $fromDate = $request->get('from', Carbon::today('America/Bogota')->toDateString());
        $toDate = $request->get('to', Carbon::today('America/Bogota')->toDateString());
        $search = $request->get('search');

        // 2. Consulta de recorridos finalizados con filtros y paginación
        $todayTrips = TimeTracking::where('user_id', $user->id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('origin', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                });
            })
            ->orderBy('start_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        // 3. Cálculo de métricas para el periodo seleccionado
        $totalKm = (float) TimeTracking::where('user_id', $user->id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->whereNotNull('end_odometer')
            ->selectRaw('SUM(end_odometer - start_odometer) as total')
            ->value('total') ?? 0.0;

        $metrics = [
            'total_km' => $totalKm
        ];

        // 4. Obtener asistencia y tracking activo
        $activeTracking = $this->trackingRepository->findActiveByUser((int)$user->id);

        $activeAttendance = DB::table('user_attendance')
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();

        return view('modules.logistics.dashboard', [
            'user' => $user,
            'todayTrips' => $todayTrips,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'search' => $search,
            'activeTracking' => $activeTracking,
            'activeAttendance' => $activeAttendance,
            'metrics' => $metrics
        ]);
    }

    /**
     * Módulo de Logística con soporte de filtros por fecha
     */
    public function logisticsModule(Request $request): View
    {
        $user = Auth::user();
        $selectedDate = $request->get('date', Carbon::today('America/Bogota')->toDateString());

        // Cambiamos ->get() por ->paginate(10)
        // Esto habilita la división por páginas de 10 registros cada una
        $todayTrips = DB::table('time_tracking')
            ->where('user_id', $user->id)
            ->whereDate('start_time', $selectedDate)
            ->orderBy('start_time', 'desc')
            ->paginate(10)
            ->withQueryString(); // Importante para que no se pierda el filtro de fecha al cambiar de página

        $metrics = [
            'total_km' => (float) $this->calculateTotalKmToday((int)$user->id)
        ];

        return view('modules.logistics.dashboard', [
            'user' => $user,
            'todayTrips' => $todayTrips,
            'selectedDate' => $selectedDate,
            'activeTracking' => $this->trackingRepository->findActiveByUser((int)$user->id),
            'activeAttendance' => $this->getActiveAttendance(),
            'metrics' => $metrics
        ]);
    }

    private function getActiveAttendance()
    {
        return DB::table('user_attendance')
            ->where('user_id', Auth::id())
            ->whereNull('check_out')
            ->first();
    }

    private function calculateTotalKmToday(int $userId): float
    {
        return (float) DB::table('time_tracking')
            ->where('user_id', $userId)
            ->whereDate('start_time', Carbon::today('America/Bogota')->toDateString())
            ->whereNotNull('end_odometer')
            ->selectRaw('SUM(end_odometer - start_odometer) as total_km')
            ->value('total_km') ?? 0.0;
    }
}
