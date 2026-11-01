<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Periodo "desde - hasta" de los reportes (RF09). Por defecto, el mes actual hasta hoy.
 */
class PeriodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'desde' => $this->input('desde') ?: now()->startOfMonth()->toDateString(),
            'hasta' => $this->input('hasta') ?: now()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ];
    }

    public function attributes(): array
    {
        return ['desde' => 'fecha desde', 'hasta' => 'fecha hasta'];
    }

    public function messages(): array
    {
        return ['hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a la fecha "desde".'];
    }

    public function desde(): Carbon
    {
        return Carbon::parse($this->validated('desde'))->startOfDay();
    }

    public function hasta(): Carbon
    {
        return Carbon::parse($this->validated('hasta'))->endOfDay();
    }
}
