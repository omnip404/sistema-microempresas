<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dos periodos para comparar ventas (RF11). Por defecto: el mes actual (A, hasta hoy)
 * frente al mes anterior completo (B).
 */
class ComparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mesAnterior = now()->subMonthNoOverflow();

        $this->merge([
            'desde_a' => $this->input('desde_a') ?: now()->startOfMonth()->toDateString(),
            'hasta_a' => $this->input('hasta_a') ?: now()->toDateString(),
            'desde_b' => $this->input('desde_b') ?: $mesAnterior->copy()->startOfMonth()->toDateString(),
            'hasta_b' => $this->input('hasta_b') ?: $mesAnterior->copy()->endOfMonth()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'desde_a' => ['required', 'date'],
            'hasta_a' => ['required', 'date', 'after_or_equal:desde_a'],
            'desde_b' => ['required', 'date'],
            'hasta_b' => ['required', 'date', 'after_or_equal:desde_b'],
        ];
    }

    public function attributes(): array
    {
        return [
            'desde_a' => 'fecha desde del periodo A', 'hasta_a' => 'fecha hasta del periodo A',
            'desde_b' => 'fecha desde del periodo B', 'hasta_b' => 'fecha hasta del periodo B',
        ];
    }

    public function messages(): array
    {
        return [
            'hasta_a.after_or_equal' => 'La fecha "hasta" del periodo A no puede ser anterior a su fecha "desde".',
            'hasta_b.after_or_equal' => 'La fecha "hasta" del periodo B no puede ser anterior a su fecha "desde".',
        ];
    }

    public function periodo(string $letra, bool $inicio): Carbon
    {
        $fecha = Carbon::parse($this->validated(($inicio ? 'desde_' : 'hasta_').$letra));

        return $inicio ? $fecha->startOfDay() : $fecha->endOfDay();
    }
}
