<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\TimeTracking\Application\Actions\StartTrackingAction;
use App\Modules\Logistics\TimeTracking\Application\Actions\EndTrackingAction;
use App\Modules\Logistics\TimeTracking\Infrastructure\Models\TimeTracking;
use App\Common\Services\ServiceResult;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * TimeTrackingController
 * Gestiona el ciclo de vida de los viajes operativos (Tracking).
 *
 * Objetivo UI:
 * - Los errores (validación/negocio) deben verse dentro del modal correspondiente.
 * - Para eso usamos Error Bags separados: startTrip / endTrip
 * - Y una bandera: session('open_modal') para reabrir el modal.
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
        // ================================================================
        // 1) REGLA: Debe existir jornada activa
        // ================================================================
        $activeAttendance = DB::table('user_attendance')
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->whereNull('check_out')
            ->exists();

        if (!$activeAttendance) {
            // ✅ Error dentro del modal START
            return redirect()
                ->back()
                ->withInput()
                ->with('open_modal', 'modal-start-trip')
                ->withErrors(
                    ['attendance' => 'Debes marcar entrada en el Reloj de Personal antes de iniciar un viaje.'],
                    'startTrip'
                );
        }

        // ================================================================
        // 2) VALIDACIÓN (en bag startTrip)
        // ================================================================
        $validated = $request->validateWithBag('startTrip', [
            'vehicle_plate'  => ['required', 'string', 'max:10'],
            'origin'         => ['required', 'string', 'max:255'],
            'start_odometer' => ['required', 'numeric', 'min:0'],
        ], [
            'vehicle_plate.required'  => 'La placa del vehículo es obligatoria.',
            'vehicle_plate.max'       => 'La placa no puede exceder 10 caracteres.',
            'origin.required'         => 'El lugar de origen es obligatorio.',
            'start_odometer.required' => 'El odómetro inicial es obligatorio.',
            'start_odometer.numeric'  => 'El odómetro debe ser un número válido.',
            'start_odometer.min'      => 'El odómetro no puede ser negativo.',
        ]);

        // ================================================================
        // 3) NORMALIZACIÓN
        // ================================================================
        $vehiclePlate = strtoupper(trim((string) $validated['vehicle_plate']));
        $origin = trim((string) $validated['origin']);
        $startOdometer = (float) $validated['start_odometer'];

        // ================================================================
        // 4) EJECUCIÓN (Action)
        // ================================================================
        $result = $this->startAction->execute(
            userId: (int) Auth::id(),
            vehiclePlate: $vehiclePlate,
            origin: $origin,
            startOdometer: $startOdometer
        );

        // ✅ Si falla -> modal START
        if ($result->isFailure()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('open_modal', 'modal-start-trip')
                ->withErrors(['start' => $result->message], 'startTrip');
        }

        // ✅ Éxito global (está bien que salga arriba)
        return redirect()
            ->back()
            ->with('success', $result->message);
    }

    /**
     * Finaliza el viaje actual.
     */
    public function end(Request $request): RedirectResponse
    {
        // ================================================================
        // 1) VALIDACIÓN (en bag endTrip)
        // ================================================================
        $validated = $request->validateWithBag('endTrip', [
            'destination'  => ['required', 'string', 'max:255'],
            'end_odometer' => ['required', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ], [
            'destination.required'  => 'El destino final es obligatorio.',
            'end_odometer.required' => 'El odómetro final es obligatorio.',
            'end_odometer.numeric'  => 'El odómetro debe ser un número válido.',
            'end_odometer.min'      => 'El odómetro no puede ser negativo.',
        ]);

        // ================================================================
        // 2) EJECUCIÓN (Action)
        // ================================================================
        $result = $this->endAction->execute(
            userId: (int) Auth::id(),
            destination: trim((string) $validated['destination']),
            endOdometer: (float) $validated['end_odometer'],
            observations: $validated['observations'] ?? null
        );

        // ✅ Si falla -> modal END
        if ($result->isFailure()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('open_modal', 'modal-end-trip')
                ->withErrors(['end_odometer' => $result->message], 'endTrip');
        }

        return redirect()
            ->back()
            ->with('success', $result->message);
    }

    /**
     * Historial de viajes del usuario.
     */
    public function history(Request $request): View
    {
        $userId = Auth::id();
        $dateFilter = $request->get('date', Carbon::now('America/Bogota')->toDateString());

        $trips = TimeTracking::where('user_id', $userId)
            ->whereDate('start_time', $dateFilter)
            ->orderBy('start_time', 'desc')
            ->get();

        return view('modules.logistics.history', [
            'trips'      => $trips,
            'dateFilter' => $dateFilter,
            'user'       => Auth::user(),
        ]);
    }

    /**
     * Admin approve (si lo sigues usando aquí).
     */
    public function approve(int $id): RedirectResponse
    {
        $tracking = TimeTracking::findOrFail($id);

        $tracking->update([
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        return redirect()
            ->route('logistics.index')
            ->with('success', '¡Registro aprobado con éxito!');
    }
}
