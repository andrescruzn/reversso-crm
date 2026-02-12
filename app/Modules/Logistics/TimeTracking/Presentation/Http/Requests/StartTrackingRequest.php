<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar inicio de tracking.
 *
 * RESPONSABILIDAD:
 * - Validar datos del inicio del viaje/jornada.
 * - Normalizar inputs (placa en mayúsculas y sin espacios laterales).
 */
class StartTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ✅ NUEVO: placa obligatoria (si en tu UI es requerida)
            'vehicle_plate'  => ['required', 'string', 'max:10'],

            'origin'         => ['required', 'string', 'max:255'],

            // Yo lo dejaría required si tu negocio lo necesita, pero respeté tu nullable
            'start_odometer' => ['nullable', 'numeric', 'min:0'],

            'is_holiday'     => ['nullable', 'boolean'],
            'observations'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_plate.required' => 'La placa del vehículo es obligatoria',
            'vehicle_plate.max'      => 'La placa no puede exceder 10 caracteres',

            'origin.required'        => 'El lugar de origen es obligatorio',
            'start_odometer.numeric' => 'El odómetro debe ser un número válido',
            'start_odometer.min'     => 'El odómetro no puede ser negativo',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normaliza placa para persistencia consistente: " ugw059 " -> "UGW059"
        if ($this->has('vehicle_plate')) {
            $this->merge([
                'vehicle_plate' => strtoupper(trim((string) $this->input('vehicle_plate'))),
            ]);
        }

        // Normaliza origin por si viene con espacios laterales
        if ($this->has('origin')) {
            $this->merge([
                'origin' => trim((string) $this->input('origin')),
            ]);
        }
    }
}
