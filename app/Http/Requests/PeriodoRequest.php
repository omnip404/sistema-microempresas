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
    public const MAXIMO_ANIOS = 2;

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
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
        ];
    }

    /** Un periodo enorme arma gráficos de miles de puntos y vuelve lento el panel. */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isEmpty()
                    && Carbon::parse($this->input('hasta'))->gt(Carbon::parse($this->input('desde'))->addYears(self::MAXIMO_ANIOS))) {
                    $validator->errors()->add('hasta', 'El periodo no puede ser mayor a '.self::MAXIMO_ANIOS.' años.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['tipo' => 'tipo de reporte', 'desde' => 'fecha desde', 'hasta' => 'fecha hasta', 'proveedor_id' => 'proveedor'];
    }

    public function messages(): array
    {
        return ['hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a la fecha "desde".'];
    }

    public function tipo(): string
    {
        return $this->validated('tipo');
    }

    public function proveedorId(): ?int
    {
        $id = $this->validated('proveedor_id');

        return $id ? (int) $id : null;
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
