# Registro del loop

Una línea por iteración (la escribe el loop):
`N | AAAA-MM-DD HH:MM | Lxx | HECHA / BLOQUEADA / SIN TAREAS / LÍNEA BASE ROTA | pruebas aprobadas/total | commit | nota breve`

1 | 2026-10-31 20:59 | L01 | HECHA | 15/15 | git log: "L01: Categorías" | Categorías: CRUD admin, conteo de productos, bloqueo al eliminar con productos, bitácora y menú
2 | 2026-10-31 21:01 | L02 | HECHA | 21/21 | git log: "L02: Proveedores" | Proveedores: CRUD admin, búsqueda por nombre/NIT, baja lógica, bitácora y menú
3 | 2026-10-31 21:04 | L03 | HECHA | 28/28 | git log: "L03: Clientes" | Clientes: listar/buscar/crear/editar para ambos roles, eliminar solo admin y sin ventas, bitácora y menú
4 | 2026-10-31 21:08 | L04 | HECHA | 33/33 | git log: "L04: Servicio de compras" | CompraService registrar/anular en transacción; anulación imposible probada; 2 ciclos de prueba
5 | 2026-10-31 21:11 | L05 | HECHA | 37/37 | git log: "L05: Pantallas de compras" | compras.index/create/store/anular solo admin, carrito con costo editable y total aria-live, menú
6 | 2026-10-31 21:14 | L06 | HECHA | 43/43 | git log: "L06: Ajustes e historial de inventario" | InventarioService.ajustar (AJUSTE, nunca negativo), historial paginado por producto enlazado desde productos
7 | 2026-10-31 21:17 | L07 | HECHA | 50/50 | git log: "L07: Gestión de usuarios" | CRUD usuarios admin, contraseña opcional al editar, activar/desactivar, desbloquear, sin autodesactivación, menú
8 | 2026-10-31 21:20 | L08 | HECHA | 57/57 | git log: "L08: Bitácora" | Pantalla de solo lectura con filtros usuario/tabla/fechas, 25 por página, detalle legible; INGRESO y SALIDA desde LoginController
9 | 2026-10-31 21:23 | L09 | HECHA | 63/63 | git log: "L09: Reporte de ventas por periodo" | Reportes: ventas por día con totales, CSV (BOM, ;, coma decimal), impresión del navegador, validación de fechas; ReporteService y PeriodoRequest reutilizables
10 | 2026-10-31 21:27 | L10 | HECHA | 72/72 | git log: "L10: Reportes de inventario" | Selector de tipo en Reportes: inventario valorizado, stock bajo, más vendidos (top 10); CSV de cada uno; 2 ciclos de prueba (orden de stock bajo)
