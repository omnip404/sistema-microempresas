# Hallazgos de seguridad

Estados: CONFIRMADO (con prueba) · RECHAZADO (con la protección que lo impide) · NECESITA_VALIDACION
(con el dato exacto que falta) · NOTA (endurecimiento, no es vulnerabilidad) · RECOMENDACIÓN (no se corrige en el loop).
Nivel = probabilidad × impacto (1–3 cada uno): 1–2 bajo · 3–4 medio · 6–9 alto.

| ID | Unidad | Título | OWASP 2025 | Rol atacante | P | I | Nivel | Estado | Prueba | Commit |
|---|---|---|---|---|---|---|---|---|---|---|
| SEC-01 | S01 | Un Administrador puede quitarse el rol (o degradar al último admin) y dejar el sistema sin administradores | A01 | Administrador | 2 | 2 | 4 medio | CONFIRMADO, corregido | tests/Security/Sec01aTest.php | a0caaaa |
| SEC-02 | S01 | Una cuenta desactivada conserva su sesión abierta y sigue operando (solo se verifica `activo` en el login) | A07 | Vendedor desactivado | 2 | 2 | 4 medio | CONFIRMADO, corregido (middleware `activo`) | tests/Security/Sec01bTest.php | a0caaaa |
| SEC-03 | S01 | `VentaService::anular`/`CompraService::anular` comprueban el estado antes de la transacción y sin `lockForUpdate`: dos anulaciones simultáneas devolverían el stock dos veces | A06 | Administrador | 1 | 2 | 2 bajo | NOTA (se revisa a fondo en S07) | - | - |
| SEC-04 | S01 | `VentaController::store` valida `exists:productos,id` sin exigir `activo=true`; con una petición manipulada se venden productos desactivados | A06 | Vendedor | 2 | 1 | 2 bajo | NOTA, superada por SEC-24 (confirmado y corregido en S06) | - | - |
| SEC-05 | S01 | Sin Policies/Gates ni `authorize()` real en los FormRequest: todo el control depende del grupo de rutas con `rol:` | A01 | - | 1 | 2 | 2 bajo | NOTA (defensa en profundidad) | - | - |
| SEC-06 | S02 | Evasión del bloqueo de 3 intentos con peticiones concurrentes (lectura-modificación-escritura de `intentos_fallidos`) | A07 | Anónimo | 2 | 2 | 4 medio | CONFIRMADO, corregido (incremento atómico) | tests/Security/Sec02aTest.php | dd47168 |
| SEC-07 | S02 | Enumeración de correos por el mensaje "Cuenta bloqueada…" distinto del mensaje de fallo | A07 | Anónimo | 3 | 1 | 3 medio | CONFIRMADO, corregido (mensaje único) | tests/Security/Sec02bTest.php | dd47168 |
| SEC-08 | S02 | El cambio de contraseña no invalida las sesiones abiertas de la cuenta | A07 | Sesión robada | 2 | 2 | 4 medio | CONFIRMADO, corregido (`authenticateSessions()`) | tests/Security/Sec02dTest.php | dd47168 |
| SEC-09 | S02 | Enumeración de usuarios por tiempo de respuesta (no se ejecuta `Hash::check` si el correo no existe) | A07 | Anónimo | - | - | - | RECHAZADO: `attempt()` corre en un timebox de 200 ms (SessionGuard, `auth.timebox_duration`) | (revisión de vendor, sin prueba por inestable) | - |
| SEC-10 | S02 | Cualquier anónimo puede bloquear cuentas conocidas (incluido el Administrador) con 3 POST cada 5 min; si se bloquean todos los administradores nadie puede desbloquear | A07 | Anónimo | 2 | 2 | 4 medio | NOTA (decisión del RF01.2; mitigar con bloqueo por IP+correo) | - | - |
| SEC-11 | S02 | `throttle:10,1` solo por IP; tras un proxy sin `trustProxies` todos compartirían IP | A07 | Anónimo | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-12 | S02 | Contraseñas solo `min:8` `max:100`, sin complejidad ni cambio forzado | A07 | - | 1 | 2 | 2 bajo | NOTA | - | - |
| SEC-13 | S02 | `SESSION_SECURE_COOKIE` sin valor y `SESSION_ENCRYPT=false`; `.env.example` trae `APP_DEBUG=true` y `APP_ENV=local` | A02 | - | 1 | 2 | 2 bajo | NOTA (se revisa en S09) | - | - |
| SEC-14 | S02 | Los intentos de login fallidos y los bloqueos no se registran en la bitácora | A09 | - | 2 | 1 | 2 bajo | NOTA (se revisa en S11) | - | - |
| SEC-15 | S02 | `session.lifetime` 120 min sin timeout corto de inactividad para el Administrador | A07 | - | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-16 | S03 | Comodines `%` y `_` de la búsqueda no se escapan en el LIKE (con binding: sin inyección, solo cambia qué filas coinciden) | A05 | Vendedor/Admin | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-17 | S03 | `buscar` no se valida como cadena: `?buscar[]=x` provoca un error 500 en Clientes (alcanzable por Vendedor), Proveedores y Productos | A10 | Vendedor/Admin | 2 | 1 | 2 bajo | NOTA (se retoma en S12) | - | - |
| SEC-18 | S03 | Reportes y comparación aceptan rangos de fechas sin tope (0001-01-01 a 9999-12-31) | A06 | Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-19 | S04 | Chart.js (`chart.js@4`) y Bootstrap/Fonts por CDN sin versión exacta (Chart.js) ni `integrity`/`crossorigin` (SRI) | A03 | Cadena de suministro | 1 | 3 | 3 medio | NOTA (se revisa en S10) | - | - |
| SEC-20 | S04 | Sin Content-Security-Policy ni otras cabeceras de seguridad: hoy no hay vector de XSS (0 `{!! !!}`, 0 innerHTML), la CSP sería la segunda defensa | A02 | - | 1 | 2 | 2 bajo | NOTA (se revisa en S09) | - | - |
| SEC-21 | S05 | Cookie de sesión: `SESSION_SECURE_COOKIE` sin fijar y `same_site=lax` por defecto; valorar `true` y `strict` para uso interno (repite SEC-13 y `APP_DEBUG` de `.env.example`, se resuelve en S09) | A02 | - | 1 | 2 | 2 bajo | NOTA | - | - |
| SEC-22 | S05 | `DELETE /productos/{producto}` existe sin formulario en las vistas; protegida por CSRF y por rol, sin riesgo | A01 | - | 1 | 1 | 1 bajo | NOTA (informativa) | - | - |
| SEC-23 | S06 | Referencias en arreglo (`cliente_id[]`, `items[n][producto_id][]`) dan error 500 en ventas y saltan `distinct` (líneas duplicadas en compras) | A10 | Vendedor/Administrador | 2 | 1 | 2 bajo | CONFIRMADO, corregido (`integer` en ids) | tests/Security/Sec06aTest.php | 87637ce |
| SEC-24 | S06 | El servidor acepta vender productos dados de baja y comprar a proveedores/productos inactivos (la baja lógica solo se respeta en la vista). Absorbe SEC-04 | A06 | Vendedor/Administrador | 2 | 1 | 2 bajo | CONFIRMADO, corregido (`Rule::exists(...)->where('activo', true)`) | tests/Security/Sec06bTest.php | 87637ce |
| SEC-25 | S06 | Sin tope máximo en numéricos (`costo_unitario`, precios, `stock_minimo`, `cantidad`); valores gigantes o `1e400` dan 500 con rollback; el stock podría rebasar unsignedInteger | A10 | Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-26 | S06 | `costo_unitario` con más de 2 decimales: `compras.total` (float sin redondear) no coincide con la suma de líneas redondeadas | A06 | Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-27 | S06 | Se permiten `precio_venta = 0` y `costo_unitario = 0` (min:0) | A06 | Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-28 | S06 | `items` sin `max` de líneas en venta y compra (cada línea toma un `lockForUpdate`) | A06 | Vendedor/Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-29 | S06 | `$fillable` incluye `stock`, `total`, `estado`, `usuario_id`: hoy ningún flujo pasa datos del request, pero un `create($request->validated())` futuro los dejaría asignables | A06 | - | 1 | 2 | 2 bajo | NOTA (defensa en profundidad) | - | - |
| SEC-30 | S06 | Eliminar cliente: `ventas()->exists()` y `delete()` sin transacción, y la FK es `nullOnDelete`; una venta concurrente quedaría sin cliente | A06 | Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-31 | S06 | La unicidad de `codigo`, `email`, `nit` y nombre de categoría depende de la collation (MySQL ci, SQLite no): las pruebas no reflejan producción | A06 | - | 1 | 1 | 1 bajo | NOTA | - | - |
| SEC-32 | S06 | `nro_comprobante` sin unicidad por proveedor (compras duplicadas); ajuste de inventario permitido sobre productos inactivos y `sumar` sin tope | A06 | Administrador | 1 | 1 | 1 bajo | NOTA | - | - |
