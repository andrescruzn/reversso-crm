<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Overtime\Application\Services\OvertimeCalculatorService;
use App\Modules\Logistics\TimeTracking\Infrastructure\Persistence\EloquentTimeTrackingRepository;
use Illuminate\Http\Request; // Importación crucial para solucionar el ArgumentCountError
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
     * Home principal del CRM
     */
    public function index(): View
    {
        return view('crm.home', [
            'user' => Auth::user(),
            'activeAttendance' => $this->getActiveAttendance()
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
