<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\TimeTracking\Application\Actions\StartTrackingAction;
use App\Modules\Logistics\TimeTracking\Application\Actions\EndTrackingAction;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking; // IMPORTANTE: Importación del modelo
use App\Common\Services\ServiceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

/**
 * Clase TimeTrackingController
 * Gestiona el ciclo de vida de los viajes operativos (Tracking).
 */
class TimeTrackingController extends Controller
{
    public function __construct(
        private readonly StartTrackingAction $startAction,
        private readonly EndTrackingAction $endAction
    ) {}

    /**
     * Inicia un nuevo viaje logístico.
     */
    public function start(Request $request): RedirectResponse
    {
        // Regla de Negocio: Verificar que exista una jornada activa (Cumple SRP)
        $activeAttendance = DB::table('user_attendance')
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->whereNull('check_out')
            ->exists();

        if (!$activeAttendance) {
            return redirect()->back()->with('error', 'Debes marcar entrada en el Reloj de Personal antes de iniciar un viaje.');
        }

        $result = $this->startAction->execute(
            userId: (int) Auth::id(),
            origin: $request->get('origin', 'Base Operativa'),
            startOdometer: (float) $request->get('start_odometer')
        );

        return $this->handleResult($result);
    }

    /**
     * Finaliza el viaje actual.
     */
    public function end(Request $request): RedirectResponse
    {
        $result = $this->endAction->execute(
            userId: (int) Auth::id(),
            destination: $request->get('destination', 'Base Operativa'),
            endOdometer: (float) $request->get('end_odometer'),
            observations: $request->get('observations')
        );

        return $this->handleResult($result);
    }

    /**
     * Historial de viajes del usuario.
     */
    public function history(Request $request): View
    {
        $userId = Auth::id();
        $dateFilter = $request->get('date', Carbon::now('America/Bogota')->toDateString());

        $trips = TimeTracking::where('user_id', $userId) // Usamos el modelo en lugar de DB table para aprovechar casts
            ->whereDate('start_time', $dateFilter)
            ->orderBy('start_time', 'desc')
            ->get();

        return view('modules.logistics.history', [
            'trips'      => $trips,
            'dateFilter' => $dateFilter,
            'user'       => Auth::user()
        ]);
    }

    /**
     * Aprueba un registro de tiempo (Acción Administrativa).
     */
    public function approve(int $id): RedirectResponse
    {
        // Localización del recurso mediante el Modelo importado
        $tracking = TimeTracking::findOrFail($id);

        // Actualización de auditoría administrativa
        $tracking->update([
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        // Redirección con Flash Message para la alerta configurada en el Dashboard
        return redirect()
            ->route('logistics.index')
            ->with('status', '¡Registro aprobado con éxito!');
    }

    /**
     * Centralización de respuesta (DRY).
     */
    private function handleResult(ServiceResult $result): RedirectResponse
    {
        $type = $result->isSuccess() ? 'success' : 'error';

        return redirect()
            ->back()
            ->with($type, $result->message);
    }
}
