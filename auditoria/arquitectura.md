# Arquitectura para la auditoría (contexto compartido)

Laravel 12 / PHP 8.2. Sesión por cookie, vistas Blade (Bootstrap 5.3.3 y Atkinson Hyperlegible por CDN),
Chart.js@4 (sin versión exacta) en panel y comparación; observador `app/Observers/BitacoraObserver.php`. Pruebas en SQLite en memoria (`tests/Feature`). Sin API ni JSON público.
Solo hay un middleware propio: `rol` → `App\Http\Middleware\VerificarRol` (403 si el usuario no tiene el rol).
`bootstrap/app.php` no define cabeceras de seguridad ni manejo de excepciones propio.

## Código
- Controladores en `app/Http/Controllers/` (Auth/LoginController, Bitacora, Categoria, Cliente, Comparacion,
  Compra, Inventario, Panel, Producto, Proveedor, Reporte, Usuario, Venta).
- Servicios con la lógica de negocio: `app/Services/` (VentaService, CompraService, InventarioService,
  ReporteService). Reportes con consultas agregadas (varios `selectRaw`/`DB::`).
- Modelos en `app/Models/` (Usuario, Rol, Producto, Categoria, Proveedor, Cliente, Venta, DetalleVenta, Compra,
  DetalleCompra, MovimientoInventario, Bitacora). Un observador registra cambios de datos en la bitácora.
- Vistas en `resources/views/<modulo>/` + `layouts/app.blade.php` + `partials/recursos` (CDN) y `accesibilidad`.
- Migraciones: `2026_10_31_00000{1,2,3}_*` (catálogo, ventas/compras, inventario/bitácora).

## Rutas por rol (`php artisan route:list --except-vendor`)
**Anónimo (`guest`):** GET `/login`; POST `/login` (`throttle:10,1`).
**Autenticado (`auth`), cualquier rol (Administrador o Vendedor):**
POST `/logout`; `/` → redirige a `/ventas/nueva`; GET `/ventas/nueva`; POST `/ventas` (VentaController@store);
`clientes` index/create/store/edit/update (sin destroy).
**Solo Administrador (`auth` + `rol:Administrador`):** `/panel`; `productos` (resource sin show) +
`/productos/{p}/movimientos`, GET/POST `/productos/{p}/ajuste`; `usuarios` (index/create/store/edit/update) +
POST `/usuarios/{u}/estado` y `/usuarios/{u}/desbloquear`; `/reportes` y GET `/reportes/exportar` (CSV);
`/comparar-periodos`; `/bitacora`; `compras` (index, nueva, store, POST `{c}/anular`); DELETE `/clientes/{c}`;
`categorias` y `proveedores` (resource sin show); GET `/ventas`; POST `/ventas/{v}/anular`.
**Fuera de auth:** `/up` (health check de Laravel).

## Límites de confianza
1. Anónimo → login (credenciales, bloqueo a 3 intentos / 5 min, throttle 10/min por IP).
2. Vendedor → solo ventas y clientes; todo lo demás exige `rol:Administrador`.
3. Administrador → todo, incluida gestión de usuarios y bitácora.
4. Navegador → servidor: campos de formularios, filtros GET (fechas, búsqueda, periodos), carritos de venta y
   compra armados con JavaScript (totales y precios se recalculan en el cliente: el servidor debe recalcularlos).
5. Servidor → Excel: `GET /reportes/exportar` entrega CSV con nombres de productos/clientes/categorías
   (riesgo de inyección de fórmulas; candidato conocido: `ReporteController::celda`).
6. Navegador → CDN: Bootstrap/Fonts/Chart.js cargados de terceros.

## Entradas de datos y dónde se guardan
- Login: email, password → `users` (intentos_fallidos, bloqueado_hasta), `bitacora` (INGRESO/SALIDA con IP).
- Usuarios: nombre, email, contraseña, rol, activo → `users`. Estado/desbloqueo por POST.
- Productos/Categorías/Proveedores/Clientes: texto libre (nombre, contacto, etc.), precios, stock mínimo.
- Ventas: cliente, productos, cantidades, precios → `ventas`, `detalle_ventas`, `movimientos_inventario`.
- Compras: proveedor, productos, cantidades, costos → `compras`, `detalle_compras`, `movimientos_inventario`.
- Ajuste de inventario: cantidad y motivo → `movimientos_inventario`.
- Reportes/Comparación/Bitácora: solo lectura con filtros GET; la bitácora guarda detalle JSON.
- Anulaciones (venta/compra): acción destructiva solo Administrador, revierte stock.

## Dónde mirar según la unidad
Acceso: `routes/web.php`, `VerificarRol`. Login: `Auth/LoginController`, `Usuario`. SQL: `ReporteService`,
`PanelController`, `ComparacionController`, controladores con búsqueda. XSS: `resources/views/**`
(carritos en `ventas/create`, `compras/create`). CSV: `ReporteController`. Config: `config/session.php`,
`.env.example` (no leer `.env`). Errores: `resources/views/errors` (si existe), servicios con transacciones.
