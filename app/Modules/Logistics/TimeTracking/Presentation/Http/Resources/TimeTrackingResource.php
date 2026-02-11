<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para transformar TimeTracking a JSON.
 *
 * RESPONSABILIDAD:
 * Formatear datos de tracking para respuestas API.
 */
class TimeTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'start_time' => $this->start_time->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'origin' => $this->origin,
            'destination' => $this->destination,
            'start_odometer' => $this->start_odometer,
            'end_odometer' => $this->end_odometer,
            'kilometers' => $this->getKilometers(),
            'is_holiday' => $this->is_holiday,
            'observations' => $this->observations,
            'status' => $this->getStatus()->value,
            'status_display' => $this->getStatus()->displayName(),
            'duration_hours' => $this->getDurationInHours(),
            'approved_by' => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
