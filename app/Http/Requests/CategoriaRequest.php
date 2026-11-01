<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('categoria')?->id;

        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('categorias', 'nombre')->ignore($id)],
            'descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return [
            'descripcion' => 'descripción',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ];
    }
}
