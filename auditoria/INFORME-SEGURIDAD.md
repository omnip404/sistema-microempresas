# Informe de la auditoría de seguridad

Sistema Web de Control y Gestión de Microempresas (Laravel 12 / PHP 8.2) · rama `claude/auditoria-seguridad-1c046a`
Método: adaptado de la *security-audit skill* de Cloudflare y de las reglas de Project CodeGuard (CoSAI), con OWASP Top 10:2025.
Quien verifica un hallazgo nunca es quien lo encontró: un subagente `cazador-seguridad` busca (solo lectura) y un
`verificador-seguridad` nuevo por candidato intenta refutarlo o lo demuestra con una prueba en `tests/Security/`.
Detalle completo: `auditoria/hallazgos.md` (71 filas), `auditoria/cobertura.md`, `auditoria/registro.md`, `auditoria/arquitectura.md`.

## 1. Resumen ejecutivo

- Se auditaron las 13 unidades (S00–S12). Se revisaron 71 puntos: **16 vulnerabilidades confirmadas y corregidas**,
  1 candidato rechazado, 4 recomendaciones que el loop no puede aplicar y 50 notas de endurecimiento.
- La suite pasó de **81 a 102 pruebas**, todas en verde. Las 21 nuevas están en `tests/Security/` (16 archivos) y la
  suite `Security` se añadió a `phpunit.xml` (antes `php artisan test` no las habría ejecutado).
- Lo más grave: **SEC-44**, las cuentas semilla con la contraseña pública `password` (README incluido) se creaban
  también en producción. Ya no: en producción el seeder usa `SEED_PASSWORD` o una contraseña aleatoria.
- Otros riesgos medios corregidos: doble devolución o descuento de stock al anular dos veces a la vez (SEC-33/34),
  evasión del bloqueo de login por concurrencia (SEC-06), sesiones que seguían vivas tras desactivar la cuenta o
  cambiar su contraseña (SEC-02/08), enumeración de cuentas por el login (SEC-07/65), un Administrador que podía
  dejar el sistema sin administradores (SEC-01) y los recursos de CDN sin SRI (SEC-52).
- El candidato conocido, la **inyección de fórmulas en el CSV** (`ReporteController::celda`), se confirmó (SEC-40) y se
  corrigió junto con una segunda causa en `fputcsv` (SEC-41).
- No se encontró inyección SQL (S03), XSS (S04) ni fallos de CSRF (S05).
- Limitaciones: no se ejecutó `composer audit` (sin internet), no se leyó `.env`, las pruebas usan SQLite (la
  concurrencia se simuló, no se probó contra MySQL) y los hashes SRI de SEC-52 deben validarse en un navegador.

## 2. Hallazgos por nivel

Nivel = probabilidad × impacto (1–3 cada uno): 1–2 bajo · 3–4 medio · 6–9 alto.

| Nivel | Confirmados y corregidos | Rechazados | Recomendaciones | Notas |
|---|---|---|---|---|
| Alto (6–9) | 1 (SEC-44) | 0 | 0 | 0 |
| Medio (3–4) | 9 (SEC-01, 02, 06, 07, 08, 33, 34, 52, 65) | 0 | 2 (SEC-46, 50) | 2 (SEC-10, 19) |
| Bajo (1–2) | 6 (SEC-23, 24, 40, 41, 58, 66) | 1 (SEC-09) | 2 (SEC-45, 55) | resto (SEC-54 y SEC-43 no llevan nivel) |

## 3. Hallazgos corregidos (cada uno con su prueba y commit)

| ID | Nivel | Qué era | Commit |
|---|---|---|---|
| SEC-44 | Alto | Cuentas semilla con contraseña `password` también en producción | `beb8b18` |
| SEC-01 | Medio | Un Administrador podía quitarse el rol y dejar el sistema sin administradores | `a0caaaa` |
| SEC-02 | Medio | Una cuenta desactivada seguía operando con su sesión abierta | `a0caaaa` |
| SEC-06 | Medio | Bloqueo de 3 intentos evadible con peticiones concurrentes | `dd47168` |
| SEC-07 | Medio | El mensaje "cuenta bloqueada" revelaba qué correos existen | `dd47168` |
| SEC-08 | Medio | Cambiar la contraseña no cerraba las sesiones abiertas de la cuenta | `dd47168` |
| SEC-33 | Medio | Anular dos veces a la vez una venta devolvía el stock dos veces (absorbe SEC-03) | `93a6921` |
| SEC-34 | Medio | Lo mismo con compras: descuento doble de stock | `93a6921` |
| SEC-52 | Medio | Chart.js flotante y recursos de jsDelivr sin `integrity` ni `crossorigin` | `87c04c2` |
| SEC-65 | Medio | `password[]=x` daba 500 solo con cuentas activas (oráculo de cuentas) | `bcba912` |
| SEC-23 | Bajo | Ids como arreglo en ventas/compras daban 500 y saltaban `distinct` | `87637ce` |
| SEC-24 | Bajo | El servidor aceptaba vender productos de baja y comprar a inactivos (absorbe SEC-04) | `87637ce` |
| SEC-40 | Bajo | Inyección de fórmulas en el CSV (código, producto, categoría) | `ca73100` |
| SEC-41 | Bajo | `fputcsv` con escape `\` permitía abrir una columna con fórmula | `ca73100` |
| SEC-58 | Bajo | Login fallido y bloqueo sin rastro en la bitácora (absorbe SEC-14) | `db08eb5` |
| SEC-66 | Bajo | `?buscar[]=x` daba 500 (Vendedor en Clientes) (absorbe SEC-17) | `bcba912` |

Rechazado: **SEC-09** (enumeración por tiempo de respuesta). `Auth::attempt` corre en un timebox de 200 ms en
Laravel, así que el correo inexistente tarda igual que el existente.

## 4. Hallazgos que necesitan validación

- **SEC-52 (SRI):** los hashes SHA-384 de Chart.js 4.6.1, Bootstrap 5.3.3 JS y CSS los calculó la sesión principal
  descargando los archivos; no se pudieron comprobar aquí. Un hash incorrecto hace que el navegador **bloquee** el
  archivo (la interfaz quedaría sin estilos o sin gráficos). Validar abriendo `/login`, `/panel` y `/comparar-periodos`
  con la consola del navegador abierta antes de fusionar.
- **Concurrencia (SEC-06, 33, 34):** las pruebas simulan la intercalación de peticiones con SQLite. Conviene probar
  con MySQL real dos anulaciones simultáneas de la misma venta.
- **SEC-68:** el filtro de fechas de la bitácora podría dar 500 en MySQL estricto con valores como `tomorrow`; no
  se pudo verificar sin MySQL.
- **`composer audit`:** no se ejecutó (sin internet). Ejecutarlo antes de entregar (SEC-54).

## 5. Recomendaciones para el equipo (no las aplica el loop)

1. **SEC-45 / SEC-20 / SEC-42:** un middleware único con `Content-Security-Policy` (permitiendo los CDN),
   `X-Frame-Options`/`frame-ancestors`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` y, con HTTPS, HSTS.
2. **SEC-46 / SEC-13 / SEC-21:** `.env.example` trae `APP_DEBUG=true`; en producción `APP_ENV=production`,
   `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true` (y valorar `SESSION_SAME_SITE=strict`).
   Advertirlo en el README.
3. **SEC-50:** servir siempre con el DocumentRoot en `public/` (con XAMPP, no apuntar a la raíz del proyecto).
4. **SEC-55:** desplegar con `composer install --no-dev --optimize-autoloader`.
5. **SEC-10 (decisión del grupo):** cualquier anónimo puede bloquear cuentas conocidas con 3 POST cada 5 minutos
   (RF01.2). Mitigar con bloqueo por IP + correo y tener un procedimiento para desbloquear al último administrador.

## 6. Notas de endurecimiento (no son vulnerabilidades)

- **Sesión y login:** throttle solo por IP y sin `trustProxies` (SEC-11, 47); contraseña solo `min:8` (SEC-12);
  sesión de 120 min (SEC-15).
- **Validación:** comodines `%`/`_` en las búsquedas (SEC-16); rangos de fechas y numéricos sin tope (SEC-18, 25,
  70); precios en cero (SEC-27); `$fillable` amplio y falta de Policies (SEC-05, 29); `integer` pendiente en
  `categoria_id`/`rol_id` (SEC-67).
- **Negocio:** posible deadlock por el orden de los ítems (SEC-35); sin protección contra doble envío (SEC-36);
  anular una compra sin motivo ni restaurar `precio_compra` (SEC-37, 39); totales en float (SEC-26).
- **Bitácora:** `deleted` sin detalle, `updated` sin valores anteriores, exportaciones sin registro, inmutabilidad
  solo por convención, horas en UTC (SEC-59 a 64).
- **Dependencias y limpieza:** Google Fonts sin SRI (SEC-53), `allow-plugins` sobrante (SEC-56), `package.json`
  sin uso (SEC-57), `welcome.blade.php` huérfana (SEC-49), sin `resources/views/errors` (SEC-48; otro trabajador
  corrige las páginas de error en main).

## 7. Cobertura de cada unidad

| Unidad | Tema | Candidatos | Confirmados | Corregidos | Notas / recomendaciones |
|---|---|---|---|---|---|
| S00 | Reconocimiento (`arquitectura.md`, 47 rutas) | 0 | 0 | 0 | — |
| S01 | Control de acceso (A01) | 2 | 2 | 2 | 3 |
| S02 | Autenticación y sesión (A07) | 4 | 3 | 3 | 1 rechazado, 6 notas |
| S03 | Inyección SQL (A05) | 0 | 0 | 0 | 3 (sin inyección) |
| S04 | XSS y salida (A05) | 0 | 0 | 0 | 2 (sin vectores de XSS) |
| S05 | CSRF y métodos HTTP (A01) | 0 | 0 | 0 | 2 (CSRF completo) |
| S06 | Validación y asignación masiva (A05/A06) | 2 | 2 | 2 | 8 |
| S07 | Lógica de negocio y concurrencia (A06) | 2 | 2 | 2 | 5 |
| S08 | Exportaciones CSV (A05) | 2 | 2 | 2 | 2 |
| S09 | Configuración y cabeceras (A02) | 1 | 1 | 1 | 3 recomendaciones, 5 notas |
| S10 | Dependencias y cadena de suministro (A03) | 1 | 1 | 1* | 1 recomendación, 5 notas |
| S11 | Registro y alertas (A09) | 1 | 1 | 1 | 6 |
| S12 | Errores y condiciones excepcionales (A10) | 2 | 2 | 2 | 5 |

\* SEC-52 se corrigió después de cerrar la unidad, con los hashes que entregó la sesión principal.

## 8. Cambios al repositorio

- Código de la aplicación: `VerificarCuentaActiva` (nuevo middleware `activo`), `UsuarioRequest`, `LoginController`,
  `VentaController`, `CompraController`, `VentaService`, `CompraService`, `ReporteController`, `ClienteController`,
  `ProveedorController`, `ProductoController`, `DatabaseSeeder`, `bootstrap/app.php` (`authenticateSessions()`),
  `routes/web.php`, cuatro vistas (SRI) y `README.md`.
- Pruebas: 16 archivos en `tests/Security/`, suite `Security` en `phpunit.xml` y `tests/TestCase.php`
  (cada `actingAs` reinicia la sesión, necesario porque `authenticateSessions()` invalida la sesión al cambiar de
  usuario dentro de una misma prueba; no se tocó ninguna prueba existente).
- Todos los commits están en la rama local; no se hizo `git push`. La revisión y la fusión a `main` quedan a
  cargo del usuario.
