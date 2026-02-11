<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar inicio de tracking.
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
            'origin' => ['required', 'string', 'max:255'],
            'start_odometer' => ['nullable', 'numeric', 'min:0'],
            'is_holiday' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin.required' => 'El lugar de origen es obligatorio',
            'start_odometer.numeric' => 'El odómetro debe ser un número válido',
            'start_odometer.min' => 'El odómetro no puede ser negativo',
        ];
    }
}
