<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Overtime\Application\Services\OvertimeCalculatorService;
use App\Modules\Logistics\TimeTracking\Domain\Contracts\TimeTrackingRepositoryInterface;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OvertimeCalculatorService $overtimeService,
        private readonly TimeTrackingRepositoryInterface $trackingRepository
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        // ✅ Admin: NO ve asistencia, NO marca entrada/salida
        if ($user->hasRole('Administrador')) {
            return view('crm.admin_home', [
                'user' => $user,
            ]);
        }

        // ✅ Conductor (y futuros roles operativos): ve asistencia
        return view('crm.home', [
            'user'             => $user,
            'activeAttendance' => $this->getActiveAttendance(),
        ]);
    }

    /**
     * Vista de logística SOLO para CONDUCTOR.
     * REGLA: siempre filtra por usuario autenticado.
     */
    public function logisticsModule(Request $request): View
    {
        $user = Auth::user();

        // ✅ Seguridad: logística operativa es SOLO Conductor
        abort_unless($user->hasRole('Conductor'), 403);

        $from = $request->get('from')
            ? Carbon::parse((string) $request->get('from'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->get('to')
            ? Carbon::parse((string) $request->get('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        // ✅ Query base: SOLO registros del usuario
        $baseQuery = TimeTracking::query()
            ->where('user_id', (int) $user->id)
            ->whereBetween('start_time', [$from, $to])
            ->orderBy('start_time', 'desc');

        // ✅ Métricas sobre TODO el rango (no sobre el paginado)
        $metrics = $this->getMetrics((clone $baseQuery)->get());

        // ✅ Paginación
        $trips = $baseQuery->paginate(10)->withQueryString();

        return view('modules.logistics.dashboard', [
            'user'             => $user,
            'trips'            => $trips,
            'filters'          => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'activeTracking'   => $this->trackingRepository->findActiveByUserId((int) $user->id),
            'activeAttendance' => $this->getActiveAttendance(), // <- para bloquear "Iniciar viaje" si no hay jornada
            'metrics'          => $metrics,
        ]);
    }

    private function getActiveAttendance(): ?object
    {
        return DB::table('user_attendance')
            ->where('user_id', Auth::id())
            ->whereNull('check_out')
            ->first();
    }

    /**
     * @param \Illuminate\Support\Collection<int, mixed> $records
     */
    private function getMetrics($records): array
    {
        return [
            'total_trips' => $records->count(),

            // ✅ suma de km recorridos SOLO en viajes cerrados (con odómetro final)
            'total_km' => (float) $records->sum(function ($t) {
                if ($t->end_odometer === null || $t->start_odometer === null) {
                    return 0;
                }
                return max(0, (int) $t->end_odometer - (int) $t->start_odometer);
            }),

            // ✅ horas SOLO cerradas (con end_time)
            'total_hours' => (float) $records->sum(function ($t) {
                if (!$t->end_time) {
                    return 0;
                }
                return Carbon::parse($t->start_time)->diffInHours(Carbon::parse($t->end_time));
            }),
        ];
    }
}
