<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cada actingAs equivale a un inicio de sesión nuevo: sin esto, la sesión de la prueba
     * conserva el hash de contraseña del usuario anterior y AuthenticateSession (que invalida
     * las sesiones al cambiar la contraseña) cerraría la sesión al cambiar de usuario.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        $this->flushSession();

        return parent::actingAs($user, $guard);
    }
}
