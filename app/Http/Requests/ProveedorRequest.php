<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('proveedor')?->id;

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'nit' => ['nullable', 'string', 'max:20', Rule::unique('proveedores', 'nit')->ignore($id)],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:150'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nit' => 'NIT',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
        ];
    }

    public function messages(): array
    {
        return [
            'nit.unique' => 'Ya existe un proveedor con ese NIT.',
        ];
    }
}
