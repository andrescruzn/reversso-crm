<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Presentation\Http\Controllers;

use App\Common\Http\Responses\ApiResponse;
use App\Modules\Logistics\TimeTracking\Application\Services\TimeTrackingService;
use App\Modules\Logistics\TimeTracking\Presentation\Http\Requests\EndTrackingRequest;
use App\Modules\Logistics\TimeTracking\Presentation\Http\Requests\StartTrackingRequest;
use App\Modules\Logistics\TimeTracking\Presentation\Http\Resources\TimeTrackingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Controlador de Time Tracking.
 *
 * RESPONSABILIDAD:
 * Manejar peticiones HTTP para gestión de registros de tiempo.
 */
class TimeTrackingController extends Controller
{
    public function __construct(
        private readonly TimeTrackingService $service
    ) {}

    // =====================================================================
    // OPERACIONES DE CONDUCTORES
    // =====================================================================

    /**
     * Iniciar jornada de trabajo.
     *
     * POST /api/v1/time-tracking/start
     */
    public function start(StartTrackingRequest $request): JsonResponse
    {
        // -----------------------------------------------------------------
        // Nota: vehicle_plate debe viajar hasta el servicio, si no, jamás
        // se persistirá aunque exista la columna en la BD.
        // -----------------------------------------------------------------
        $result = $this->service->startTracking(
            userId: (int) auth()->id(),
            vehiclePlate: $request->input('vehicle_plate'), // ✅ NUEVO
            origin: $request->input('origin'),
            startOdometer: (int) $request->input('start_odometer'),
            isHoliday: $request->boolean('is_holiday'),
            observations: $request->input('observations')
        );

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::created(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    /**
     * Finalizar jornada de trabajo.
     *
     * POST /api/v1/time-tracking/end
     */
    public function end(EndTrackingRequest $request): JsonResponse
    {
        $result = $this->service->endTracking(
            userId: (int) auth()->id(),
            destination: $request->input('destination'),
            endOdometer: (int) $request->input('end_odometer'),
            observations: $request->input('observations')
        );

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::success(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    public function active(): JsonResponse
    {
        $result = $this->service->getActiveTracking((int) auth()->id());

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode,
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    public function myHistory(Request $request): JsonResponse
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status'),
        ];

        $result = $this->service->getUserHistory(
            userId: (int) auth()->id(),
            filters: array_filter($filters),
            page: (int) $request->input('page', 1),
            limit: (int) $request->input('limit', 20)
        );

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::paginated(
            items: TimeTrackingResource::collection($result->data['items']),
            total: $result->data['total'],
            page: $result->data['page'],
            limit: $result->data['limit'],
            message: $result->message
        );
    }

    // =====================================================================
    // OPERACIONES DE ADMINISTRADORES
    // =====================================================================

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'user_id' => $request->input('user_id'),
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'is_holiday' => $request->input('is_holiday'),
        ];

        $result = $this->service->listAll(
            filters: array_filter($filters),
            page: (int) $request->input('page', 1),
            limit: (int) $request->input('limit', 20)
        );

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::paginated(
            items: TimeTrackingResource::collection($result->data['items']),
            total: $result->data['total'],
            page: $result->data['page'],
            limit: $result->data['limit'],
            message: $result->message
        );
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id);

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode,
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    public function approve(int $id): JsonResponse
    {
        $result = $this->service->approve(
            trackingId: $id,
            approvedBy: (int) auth()->id()
        );

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::success(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    public function unapprove(int $id): JsonResponse
    {
        $result = $this->service->unapprove($id);

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::success(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'start_odometer' => ['nullable', 'numeric', 'min:0'],
            'end_odometer' => ['nullable', 'numeric', 'min:0'],
            'is_holiday' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string', 'max:1000'],

            // ✅ si quieres permitir editar placa desde admin:
            'vehicle_plate' => ['nullable', 'string', 'max:10'],
        ]);

        $result = $this->service->update($id, $validated);

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::success(
            data: new TimeTrackingResource($result->data),
            message: $result->message
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        if ($result->isFailure()) {
            return ApiResponse::error(
                message: $result->message,
                errorCode: $result->errorCode
            );
        }

        return ApiResponse::success(
            data: null,
            message: $result->message
        );
    }
}
