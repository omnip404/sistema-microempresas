# Backlog del loop 2 – Cerrar brechas con el SRS de la Fase I

Origen: verificación requisito por requisito del SRS (Fase I) contra el código, el 01/11/2026.
Mismas reglas, convenciones y prohibiciones que `BACKLOG-LOOP.md` y `.claude/loop.md` del loop 1.

| Requisito | Estado verificado | Tarea |
|---|---|---|
| RF06.6 Comprobante de venta imprimible | Falta | L13 |
| RF09.2 Reporte de compras por fechas y proveedor | Falta | L14 |
| RF09.5 Exportación con nombre del negocio, fecha de emisión y paginación | Parcial (CSV e impresión sin encabezado) | L15 |
| RF10.1 Ganancia estimada del mes | Falta (se muestra "venta promedio") | L16 |
| RF10.2 Líneas de ventas por día del mes · RF10.3 Barras top 10 · RF10.5 Filtro de fechas | Parcial (barras de 7 días, lista de 5, sin filtro) | L17 |
| RF10.4 Gráfico circular de ventas por categoría | Falta | L18 |
| RNF05 Cerrar sesión tras 15 min de inactividad | No cumple (120 min) | L19 |
| RNF01 Panel y consultas en menos de 3 s con 10 000 ventas | Sin medir | L20 |
| RNF11 Copia de seguridad diaria | Falta (es operación del servidor) | L21 (documentar) |
| RNF13 Versionado en GitHub | Parcial (Git local) | Fuera del loop (lo publica el usuario) |

- [x] **L13 – Comprobante de venta (RF06.6)**: ruta `ventas.comprobante` (Administrador y el Vendedor que hizo la venta), vista imprimible con nombre del negocio (`config('app.name')`), n.º, fecha, cliente, vendedor, detalle, total y estado; enlace "Imprimir comprobante" tras registrar la venta y en el listado. Pruebas: acceso del dueño, 403 a otro vendedor, contenido.
- [x] **L14 – Reporte de compras (RF09.2)**: nuevo tipo en `ReporteService::TIPOS` con filtros de fechas y proveedor (opcional), misma estructura columnas/filas/totales, CSV incluido. Solo compras REGISTRADAS. Pruebas.
- [x] **L15 – Encabezado de impresión (RF09.5)**: bloque visible solo al imprimir (`d-none d-print-block`) con nombre del negocio, título del reporte, periodo y fecha/hora de emisión; numeración de páginas con CSS `@page` (contador `counter(page)`). Prueba de que el HTML incluye el encabezado.
- [x] **L16 – Ganancia estimada del mes (RF10.1)**: Σ cantidad × (precio_unitario − precio_compra del producto) de ventas REGISTRADAS del mes, calculada en `ReporteService` (mover allí también las consultas de `PanelController`). Indicador en el panel con la aclaración "estimada". Prueba con valores conocidos.
- [ ] **L17 – Gráficos del panel (RF10.2, 10.3, 10.5)**: líneas de ventas por día del periodo, barras de los 10 más vendidos, filtro desde/hasta (por defecto el mes actual) reutilizando `PeriodoRequest`; tabla alternativa accesible para cada gráfico. Pruebas de datos y validación.
- [ ] **L18 – Ventas por categoría (RF10.4)**: gráfico circular (dona) con leyenda y etiquetas de valor, más tabla alternativa; colores validados con el validador de la skill dataviz si está disponible, si no, la paleta del proyecto. Prueba de agregación.
- [ ] **L19 – Sesión de 15 minutos (RNF05)**: `SESSION_LIFETIME=15` en `.env.example` y valor por defecto 15 en `config/session.php`; nota en README. Prueba que verifique la configuración.
- [ ] **L20 – Prueba de rendimiento (RNF01)**: seeder `RendimientoSeeder` (no se ejecuta en el seeder principal) que crea 10 000 ventas; prueba marcada `@group rendimiento` que mide el tiempo del panel y de los reportes y falla si supera 3 s; índices nuevos solo si hacen falta (migración nueva). Registrar los tiempos en `LOOP-LOG.md`.
- [ ] **L21 – Copias de seguridad (RNF11)**: comando `php artisan respaldo:crear` que copia la base SQLite o ejecuta `mysqldump` según el motor, guardando en `storage/respaldos` con fecha; programado a diario en `routes/console.php` (`Schedule::command(...)->daily()`); README con cómo activar el programador en el servidor. Pruebas del comando con SQLite.
- [ ] **L22 – Cierre**: README y `LOOP-RESUMEN.md` actualizados; `vendor/bin/pint`; `php artisan test --compact` en verde.
