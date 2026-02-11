<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar finalización de tracking.
 */
class EndTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'end_odometer' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination.required' => 'El lugar de destino es obligatorio',
            'end_odometer.numeric' => 'El odómetro debe ser un número válido',
            'end_odometer.min' => 'El odómetro no puede ser negativo',
        ];
    }
}
