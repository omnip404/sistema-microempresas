<?php

namespace App\Http\Requests;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('usuarios', 'email')->ignore($usuario?->id)],
            'rol_id' => ['required', 'exists:roles,id'],
            // Al crear la contraseña es obligatoria; al editar solo cambia si se escribe una
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'activo' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $usuario = $this->route('usuario');

                if ($usuario && $usuario->is($this->user()) && ! $this->boolean('activo', true)) {
                    $validator->errors()->add('activo', 'No puede desactivar su propia cuenta.');
                }

                // Siempre debe quedar al menos un administrador activo
                $rolAdmin = Rol::where('nombre', Rol::ADMINISTRADOR)->value('id');

                if ($usuario && $usuario->rol_id === $rolAdmin && (int) $this->input('rol_id') !== $rolAdmin) {
                    $otros = Usuario::where('rol_id', $rolAdmin)->where('activo', true)->whereKeyNot($usuario->id)->exists();

                    if (! $otros) {
                        $validator->errors()->add('rol_id', 'Debe quedar al menos un administrador activo.');
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo',
            'rol_id' => 'rol',
            'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.min' => 'La contraseña debe tener 8 o más caracteres.',
        ];
    }
}
