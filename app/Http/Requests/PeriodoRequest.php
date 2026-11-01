<?php

namespace App\Http\Requests;

use App\Services\ReporteService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tipo de reporte y periodo "desde - hasta" (RF09). Por defecto, ventas del mes actual hasta hoy.
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
            'tipo' => $this->input('tipo') ?: ReporteService::VENTAS,
            'desde' => $this->input('desde') ?: now()->startOfMonth()->toDateString(),
            'hasta' => $this->input('hasta') ?: now()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(array_keys(ReporteService::TIPOS))],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ];
    }

    public function attributes(): array
    {
        return ['tipo' => 'tipo de reporte', 'desde' => 'fecha desde', 'hasta' => 'fecha hasta'];
    }

    public function messages(): array
    {
        return ['hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a la fecha "desde".'];
    }

    public function tipo(): string
    {
        return $this->validated('tipo');
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
