# Backlog del loop – Sprints 3 y 4

Marcas: `[ ]` pendiente · `[x]` hecha · `[!]` bloqueada (con el motivo en la línea siguiente).
Una tarea por iteración, en este orden. Cada tarea termina con `php artisan test` en verde.

## Convenciones del proyecto (obligatorias)

- **Nombres en español**, como el resto del código: modelos `Proveedor`, `Compra`; tablas en plural;
  rutas `proveedores.index`; vistas `resources/views/proveedores/index.blade.php`.
- **Tablas existentes**: las 12 tablas ya están migradas (ver `database/migrations`). Si de verdad hace
  falta una columna nueva, crea una migración nueva; nunca edites una migración existente.
- **Reglas de negocio con varias tablas** van en un Service con `DB::transaction` y `lockForUpdate`,
  igual que `app/Services/VentaService.php`.
- **Validación**: Form Request (como `ProductoRequest`) con mensajes y `attributes()` en español.
- **Roles**: rutas de administración dentro del grupo `rol:Administrador` de `routes/web.php`.
- **Bitácora**: todo modelo nuevo que el usuario cree o modifique se registra en el bucle de
  `AppServiceProvider::boot()` junto a Producto, Venta, Compra y Usuario.
- **Menú**: cada pantalla nueva se agrega al arreglo `$menu` de `resources/views/layouts/app.blade.php`.
- **Vistas accesibles** (copiar el patrón de `productos/index` y `productos/form`):
  `@extends('layouts.app')`; cada campo con `<label for>`; errores con `aria-invalid` y `aria-describedby`;
  tablas con `<caption class="visually-hidden">` y `<th scope="col">`; estados con ícono y texto
  (clases `estado estado-aviso|estado-exito|estado-neutro`); botones `btn-primario`, `btn-secundario`,
  `btn-peligro`; montos con `number_format($v, 2, ',', '.')` y la clase `numero`.
- **Bajas**: lógicas (`activo = false`) cuando el registro tiene historial; borrar solo si no tiene relaciones.
- **Pruebas**: Feature tests con `RefreshDatabase` y `$this->seed(DatabaseSeeder::class)`, como `tests/Feature/VentaTest.php`.
  Cada tarea prueba como mínimo: el caso correcto, una validación que falla y que el Vendedor recibe 403
  donde corresponda.

---

## Sprint 3

- [x] **L01 – Categorías (RF03)**
  Criterios: CRUD de categorías solo para Administrador (sin `show`). El listado muestra cuántos productos
  tiene cada una. Nombre obligatorio, único, máximo 60. No se puede eliminar una categoría con productos
  (mensaje claro). Entrada "Categorías" en el menú.

- [x] **L02 – Proveedores (RF04)**
  Criterios: CRUD solo para Administrador, con búsqueda por nombre o NIT. Campos: nombre (obligatorio),
  NIT (opcional, único), teléfono, dirección, activo. Eliminar = baja lógica. Proveedor se registra en la
  bitácora. Entrada "Proveedores" en el menú.

- [ ] **L03 – Clientes (RF05)**
  Criterios: Administrador y Vendedor pueden listar, buscar (nombre o CI/NIT), crear y editar. Solo el
  Administrador elimina, y solo clientes sin ventas. Entrada "Clientes" en el menú para ambos roles.

- [ ] **L04 – Servicio de compras (RF07)**
  Criterios: `app/Services/CompraService.php` con:
  `registrar(Usuario $usuario, int $proveedorId, array $items, ?string $nroComprobante): Compra`.
  Cada ítem: `producto_id`, `cantidad` (≥ 1), `costo_unitario` (≥ 0). Suma stock, actualiza
  `productos.precio_compra`, crea movimiento ENTRADA con referencia `COMPRA-{id}` y guarda el total.
  `anular(Compra $compra, Usuario $admin)`: falla con mensaje si algún producto ya no tiene stock suficiente
  para revertir; si no, descuenta stock, crea movimiento SALIDA `ANULA-COMPRA-{id}` y marca ANULADA.
  Todo en transacción. Pruebas del servicio, incluido el caso de anulación imposible.

- [ ] **L05 – Pantallas de compras (RF07)**
  Criterios: `compras.create` (proveedor, n.º de comprobante, carrito con producto, cantidad y costo
  editable, total en vivo con `aria-live`) y `compras.index` (listado con estado y botón Anular con
  confirmación). Solo Administrador. Usa `CompraService`. Entrada "Compras" en el menú.

- [ ] **L06 – Ajustes e historial de inventario (RF08)**
  Criterios: el Administrador registra un ajuste (sumar o restar unidades) con observación obligatoria;
  crea un movimiento AJUSTE; el stock nunca queda negativo (mensaje claro). Pantalla de historial de
  movimientos por producto (fecha, tipo, cantidad, stock resultante, referencia, usuario), paginada,
  enlazada desde el listado de productos.

## Sprint 4

- [ ] **L07 – Gestión de usuarios (RF02)**
  Criterios: solo Administrador. Crear (nombre, correo único, rol, contraseña de 8 o más caracteres),
  editar (la contraseña solo cambia si se escribe), activar o desactivar, y botón "Desbloquear" que pone en
  cero `intentos_fallidos` y `bloqueado_hasta`. Un administrador no puede desactivarse a sí mismo.
  Prueba que un usuario desactivado no puede iniciar sesión.

- [ ] **L08 – Bitácora (RF12)**
  Criterios: pantalla de solo lectura para el Administrador, con filtros por usuario, tabla y rango de
  fechas, 25 filas por página, y el detalle JSON mostrado de forma legible. Registrar también INGRESO y
  SALIDA del sistema desde `LoginController`.

- [ ] **L09 – Reporte de ventas por periodo (RF09)**
  Criterios: pantalla "Reportes" con fechas desde y hasta (por defecto, el mes actual; "hasta" no puede ser
  menor que "desde"). Tabla por día: fecha, n.º de ventas, unidades y total, más una fila de totales. Solo
  ventas REGISTRADAS. Botón "Exportar a Excel (CSV)": UTF-8 con BOM, separador `;` y coma decimal, para que
  Excel en español lo abra bien. Botón "Imprimir o guardar PDF" que usa la impresión del navegador (los
  estilos `@media print` ya ocultan el menú). Pruebas: totales correctos, cabeceras y contenido del CSV,
  validación de fechas.

- [ ] **L10 – Reportes de inventario (RF09)**
  Criterios: en la misma pantalla de Reportes, un selector de tipo de reporte: inventario actual valorizado
  (stock × precio de compra, con total), stock bajo, y productos más vendidos del periodo. Cada uno con su
  exportación CSV. Pruebas de cada cálculo.

- [ ] **L11 – Comparación de periodos (RF11)**
  Criterios: pantalla "Comparar periodos" (Administrador) que compara el periodo A con el B (por defecto,
  el mes actual frente al anterior): total vendido, n.º de ventas, venta promedio y variación %. Si el
  periodo B es cero, muestra "sin datos del periodo anterior" en vez de dividir. Tabla accesible y gráfico
  de barras con el color de `--grafico`. Pruebas, incluido el caso de periodo vacío.

- [ ] **L12 – Cierre técnico**
  Criterios: actualizar la tabla de módulos del `README.md` (estado de cada módulo y sus rutas), ejecutar
  `vendor/bin/pint` en todo el proyecto, y `php artisan test` en verde. No tocar el informe ni Trello.

---

## Fuera del loop (las decide el grupo o se hacen con revisión humana)

- Nombre definitivo del sistema · Supabase/PostgreSQL o MySQL · flujo de aprobaciones y rol de almacén
- Inicio de sesión con Google · actualizar el informe, Trello y Figma · subir a GitHub
