<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Copia de seguridad diaria de la base de datos (RNF11). Requiere el programador activo en el servidor (ver README).
Schedule::command('respaldo:crear')->daily();
