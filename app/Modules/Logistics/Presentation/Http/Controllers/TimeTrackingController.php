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
use Illuminate\Support\Facades\Validator;
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
     * Registra un viaje pasado de forma manual (retroactivo).
     */
    public function manualTrip(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_plate'  => ['required', 'string', 'max:10'],
            'origin'         => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'start_time'     => ['required', 'date'],
            'end_time'       => ['required', 'date', 'after:start_time'],
            'start_odometer' => ['required', 'numeric', 'min:0'],
            'end_odometer'   => ['required', 'numeric', 'gte:start_odometer'],
            'is_holiday'     => ['nullable', 'boolean'],
            'observations'   => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle_plate.required'  => 'La placa del vehículo es obligatoria.',
            'origin.required'         => 'El punto de origen es obligatorio.',
            'destination.required'    => 'El destino es obligatorio.',
            'start_time.required'     => 'La fecha/hora de inicio es obligatoria.',
            'start_time.date'         => 'La fecha de inicio no es válida.',
            'end_time.required'       => 'La fecha/hora de fin es obligatoria.',
            'end_time.date'           => 'La fecha de fin no es válida.',
            'end_time.after'          => 'La hora de fin debe ser posterior a la de inicio.',
            'start_odometer.required' => 'El odómetro inicial es obligatorio.',
            'start_odometer.numeric'  => 'El odómetro inicial debe ser un número.',
            'end_odometer.required'   => 'El odómetro final es obligatorio.',
            'end_odometer.numeric'    => 'El odómetro final debe ser un número.',
            'end_odometer.gte'        => 'El odómetro final debe ser mayor o igual al inicial.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('open_modal', 'modal-manual-trip')
                ->withErrors($validator->errors(), 'manualTrip');
        }

        $data = $validator->validated();

        TimeTracking::create([
            'user_id'        => (int) Auth::id(),
            'vehicle_plate'  => strtoupper(trim((string) $data['vehicle_plate'])),
            'origin'         => trim((string) $data['origin']),
            'destination'    => trim((string) $data['destination']),
            'start_time'     => Carbon::parse($data['start_time']),
            'end_time'       => Carbon::parse($data['end_time']),
            'start_odometer' => (int) $data['start_odometer'],
            'end_odometer'   => (int) $data['end_odometer'],
            'is_holiday'     => $request->boolean('is_holiday'),
            'observations'   => $data['observations'] ?? null,
            'approved_by'    => null,
            'approved_at'    => null,
        ]);

        return redirect()->back()->with('success', 'Viaje registrado. Quedará pendiente de aprobación del administrador.');
    }

    /**
     * Elimina un viaje manual del conductor (solo si no está en ruta activa).
     */
    public function deleteManualTrip(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        $trip = TimeTracking::where('id', $id)
            ->where('user_id', $userId)
            ->whereNotNull('end_time')
            ->first();

        if (!$trip) {
            return redirect()->back()->with('error', 'Viaje no encontrado o no tienes permiso para eliminarlo.');
        }

        if ($trip->end_time === null) {
            return redirect()->back()->with('error', 'No puedes eliminar un viaje en curso. Finalízalo primero.');
        }

        $trip->delete();

        return redirect()->back()->with('success', 'Viaje eliminado correctamente.');
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
