<?php

namespace App\Observers;

use App\Models\Bitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Patrón Observer: registra en la bitácora (RF12) cada alta, cambio o baja
 * de los modelos observados, sin que los controladores tengan que hacerlo.
 */
class BitacoraObserver
{
    public function created(Model $model): void
    {
        $this->registrar('CREAR', $model, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->registrar('MODIFICAR', $model, $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->registrar('ELIMINAR', $model, null);
    }

    private function registrar(string $accion, Model $model, ?array $detalle): void
    {
        unset($detalle['password'], $detalle['remember_token']);

        Bitacora::create([
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'tabla' => $model->getTable(),
            'registro_id' => $model->getKey(),
            'detalle' => $detalle,
            'ip' => request()->ip(),
        ]);
    }
}
