<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('producto')?->id;

        return [
            'categoria_id' => ['required', 'exists:categorias,id'],
            'codigo' => ['required', 'string', 'max:30', Rule::unique('productos', 'codigo')->ignore($id)],
            'nombre' => ['required', 'string', 'max:120'],
            'unidad_medida' => ['required', 'string', 'max:20'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria_id' => 'categoría',
            'codigo' => 'código',
            'precio_compra' => 'precio de compra',
            'precio_venta' => 'precio de venta',
            'stock_minimo' => 'stock mínimo',
        ];
    }
}
