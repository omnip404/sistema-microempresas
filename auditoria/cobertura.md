# Cobertura de la auditoría (unidades)

Marcas: `[ ]` pendiente · `[x]` auditada. Una unidad por vuelta. Debajo de cada unidad, el loop anota
qué puntos revisó (para que la cobertura sea verificable, como pide el método de Cloudflare).
Referencias: OWASP Top 10:2025, reglas de Project CodeGuard (CoSAI) y buenas prácticas de Laravel.

- [x] **S00 – Reconocimiento**
  Crear `auditoria/arquitectura.md` (máximo 120 líneas): rutas agrupadas por rol (`php artisan route:list
  --except-vendor`), límites de confianza (anónimo → login; Vendedor; Administrador; navegador → servidor;
  CSV → Excel; scripts de CDN), entradas de datos por pantalla y dónde se guardan. Este archivo es el contexto
  compartido de todas las vueltas y subagentes: no se vuelve a explorar el proyecto completo.
  REVISADO: 47 rutas con su middleware, VerificarRol, LoginController, bootstrap/app.php, CDN y selectRaw ubicados.

- [x] **S01 – Control de acceso (A01)**
  Cada ruta con su middleware (`auth`, `rol:Administrador`); qué puede hacer el Vendedor por URL directa;
  acciones destructivas (anular, eliminar, desbloquear, desactivar) solo para Administrador; `usuario_id`
  tomado de la sesión y nunca del formulario; un Administrador no puede quitarse su propio rol o acceso.
  REVISADO: routes/web.php (todas las rutas con su middleware), VerificarRol, acciones destructivas solo en el grupo Administrador, usuario_id desde la sesión en Venta/Compra/Inventario/BitacoraObserver, autoprotección en UsuarioController/UsuarioRequest, mass assignment de Usuario/Venta, route-model binding sin datos por propietario, login con `activo`. Candidatos 2 (ambos confirmados y corregidos), notas 3.

- [x] **S02 – Autenticación y sesión (A07)**
  Bloqueo tras 3 intentos (¿se puede evadir con mayúsculas en el correo o espacios?), `throttle` del login,
  regeneración de sesión al entrar, invalidación al salir, usuario desactivado o bloqueado, enumeración de
  usuarios por mensajes distintos, reglas de contraseña, `remember_token`.
  REVISADO: bloqueo a 3 intentos (mayúsculas/espacios/collation: sin evasión), throttle, regeneración e invalidación de sesión, cuenta bloqueada/desactivada, enumeración por mensaje y por tiempo, reglas de contraseña, sesiones tras cambio de clave, remember me, `intended` (sin open redirect), config/session.php y auth.php. Candidatos 4 (3 confirmados y corregidos, 1 rechazado), notas 6.

- [x] **S03 – Inyección SQL (A05)**
  Todo `DB::raw`, `selectRaw`, `whereRaw`, `orderBy` o `LIKE` con datos del usuario; nombres de columna u
  orden tomados de la URL; comodines `%` y `_` en búsquedas.
  REVISADO: selectRaw/DB::raw de ReporteService y PanelController (SQL fijo, fechas por binding), ausencia de whereRaw/orderByRaw/DB::select, orderBy/groupBy fijos, parámetro tipo (Rule::in), fechas de PeriodoRequest/ComparacionRequest/Bitácora, LIKE con binding en Cliente/Proveedor/Producto, paginación fija. Candidatos 0, notas 3.

- [x] **S04 – XSS y salida (A05)**
  Uso de `{!! !!}`; JavaScript que arma HTML (carritos de ventas y compras, gráficos); `@json` dentro de
  `<script>`; detalle JSON de la bitácora; mensajes flash con datos del usuario; atributos `href` armados con
  datos.
  REVISADO: 0 usos de `{!! !!}` e innerHTML; carritos con createElement/textContent; @json en panel y comparación (flags seguros); detalle JSON de la bitácora con `{{ }}`; mensajes flash; atributos href/onclick/data-*; url()->previous y fullUrl (0 usos); back()/intended; vistas de impresión; welcome y partials. Candidatos 0, notas 2.

- [ ] **S05 – CSRF y métodos HTTP (A01)**
  `@csrf` en todos los formularios POST/PUT/DELETE; rutas GET que cambian datos; cierre de sesión por POST.

- [ ] **S06 – Validación y asignación masiva (A05/A06)**
  `$fillable` frente a lo que llega del formulario; uso de `validated()`; límites numéricos (cantidades
  negativas o cero, precios negativos, desbordes, decimales); longitudes máximas; IDs inexistentes;
  productos o clientes inactivos usados en ventas o compras.

- [ ] **S07 – Lógica de negocio y concurrencia (A06)**
  Stock negativo por dos ventas simultáneas; anular dos veces; anular una compra cuyo stock ya se vendió;
  manipular el total o el precio desde el navegador; ítems repetidos en el carrito; doble envío del formulario.

- [ ] **S08 – Exportaciones CSV e impresión (A05)**
  Inyección de fórmulas en Excel (celdas que empiezan con `=`, `+`, `-`, `@`, tabulación o retorno de carro,
  en nombres de productos, clientes o categorías); nombre del archivo en la cabecera; fechas de los filtros.

- [ ] **S09 – Configuración y cabeceras (A02)**
  `APP_DEBUG` y `APP_ENV` en `.env.example`; cookies de sesión (`http_only`, `same_site`, `secure`);
  cabeceras de seguridad (CSP, `X-Frame-Options` o `frame-ancestors`, `X-Content-Type-Options`,
  `Referrer-Policy`); páginas de error que muestren detalles. NO leer `.env`.

- [ ] **S10 – Dependencias y cadena de suministro (A03)**
  `composer audit`; scripts de CDN sin versión exacta ni `integrity` (Bootstrap, Chart.js, Google Fonts);
  paquetes de desarrollo cargados en producción.

- [ ] **S11 – Registro y alertas (A09)**
  Qué acciones críticas quedan en la bitácora (intentos fallidos y bloqueos incluidos); que no se guarden
  contraseñas ni tokens; quién puede ver la bitácora; inyección de saltos de línea en registros.

- [ ] **S12 – Errores y condiciones excepcionales (A10)**
  Excepciones sin capturar que devuelvan 500 con datos; fechas inválidas en filtros; división entre cero en
  la comparación de periodos; transacciones que no se revierten ante un error; mensajes que revelan rutas o SQL.
