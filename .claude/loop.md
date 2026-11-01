# Loop de implementación – Sistema de Gestión de Microempresas

Eres el desarrollador de este proyecto Laravel 12 (PHP 8.2). Cada iteración del loop implementa
COMO MÁXIMO UNA tarea de `BACKLOG-LOOP.md`. El avance vive en archivos, no en tu memoria:
léelos siempre al empezar, aunque creas recordar el estado.

## 1. Arranque (en cada iteración)

1. Lee `BACKLOG-LOOP.md` completo y las últimas 15 líneas de `LOOP-LOG.md`.
2. Cuenta las iteraciones ya registradas en `LOOP-LOG.md`. Si ya hay 16 → ve a **5. Detenerse**.
3. Ejecuta `git status --short` y `php artisan test`.
   Si hay cambios sin commit o alguna prueba falla ANTES de que toques nada, no trabajes:
   registra `LÍNEA BASE ROTA` en `LOOP-LOG.md` y ve a **5. Detenerse**.

## 2. Elegir la tarea

- Toma la primera tarea marcada `[ ]`, en orden. Ignora `[x]` (hecha) y `[!]` (bloqueada).
- Si no queda ninguna `[ ]` → ve a **5. Detenerse**.
- Si la tarea ya está hecha (el código existe y cumple sus criterios), márcala `[x]` sin rehacerla.

## 3. Implementar

- Cumple los **criterios de aceptación** de la tarea, ni más ni menos (KISS, YAGNI).
- Sigue las convenciones de `BACKLOG-LOOP.md` → sección "Convenciones del proyecto".
- Escribe las pruebas Feature que pide la tarea en `tests/Feature/`.
- Presupuesto: como máximo **3 ciclos** de "ejecutar pruebas → corregir". No pruebes la misma
  corrección dos veces; si un enfoque falla dos veces, cambia de enfoque o bloquea la tarea.

## 4. Cerrar la tarea

**Si `php artisan test` pasa completo:**
1. `vendor/bin/pint` sobre los archivos que creaste o cambiaste.
2. Vuelve a ejecutar `php artisan test` (debe seguir en verde).
3. Marca la tarea `[x]` en `BACKLOG-LOOP.md` y agrega su línea a `LOOP-LOG.md`.
4. `git add -A` y `git commit -m "Lxx: <título de la tarea>"`.

**Si tras 3 ciclos sigue fallando, o la tarea exige algo de la lista Prohibido:**
1. `git stash push -u -m "LOOP Lxx bloqueada"` (guarda el trabajo sin borrarlo).
2. Marca la tarea `[!]` y escribe el motivo en una línea debajo de ella.
3. Agrega la línea a `LOOP-LOG.md` y haz commit solo de `BACKLOG-LOOP.md` y `LOOP-LOG.md`.
4. Si la iteración anterior también terminó en BLOQUEADA → ve a **5. Detenerse**.

## 5. Siguiente iteración o detenerse

- Si quedan tareas `[ ]` y no se cumplió ninguna condición de parada: programa la siguiente
  iteración en **60 segundos** (no hay nada externo que esperar).
- Condiciones de parada: no quedan tareas `[ ]`, 16 iteraciones, línea base rota, o dos
  tareas bloqueadas seguidas. En ese caso:
  1. Escribe `LOOP-RESUMEN.md`: tareas hechas (con su commit), tareas bloqueadas (motivo y
     nombre del stash), resultado final de `php artisan test`, y qué debe revisar el equipo.
  2. Haz commit de `LOOP-RESUMEN.md`.
  3. Detén el loop (no programes otra iteración).

## Prohibido (si una tarea lo necesita, márcala `[!]` y explica)

- `git push`, `git reset --hard`, `git clean`, `git checkout -- .`, reescribir o borrar commits.
- Instalar, quitar o actualizar paquetes (`composer`, `npm`), o tocar `php.ini` o `.env`.
- Usar internet o herramientas externas: Trello, Figma, Claude Docs, Google Drive, correo.
- Cambiar decisiones que el grupo aún no tomó: nombre del sistema (`APP_NAME`), motor de base
  de datos (MySQL o PostgreSQL), flujo de aprobaciones, inicio de sesión con Google.
- Borrar o debilitar pruebas existentes para que pasen. Solo puedes ajustar una prueba si la
  tarea cambia ese comportamiento a propósito y lo dice.
- Crear, editar o borrar archivos fuera de esta carpeta del proyecto.

## Formato de `LOOP-LOG.md` (una línea por iteración)

`N | AAAA-MM-DD HH:MM | Lxx | HECHA / BLOQUEADA / SIN TAREAS / LÍNEA BASE ROTA | pruebas aprobadas/total | commit | nota breve`
