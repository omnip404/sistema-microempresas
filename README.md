# Sistema Web de Control y Gestión de Microempresas

Proyecto de la materia Ingeniería de Software (UPEA, paralelo 7toC). Laravel 12 · PHP 8.2 · MySQL 8 · Bootstrap 5 · Chart.js.

## Avance (Fase II, sprints 1–4)

Las rutas de todos los módulos son solo para el Administrador, con dos excepciones: en Ventas el Vendedor puede registrar (el Administrador además lista y anula) y en Clientes ambos roles listan, crean y editan (solo el Administrador elimina).

| Módulo | Estado | Rutas |
|---|---|---|
| Seguridad: inicio de sesión, bloqueo tras 3 intentos (5 min), roles Administrador/Vendedor | Hecho | `/login` |
| Usuarios (RF02): alta, edición, activar/desactivar, desbloquear | Hecho | `/usuarios` |
| Productos: alta, edición, baja lógica, búsqueda y filtro | Hecho | `/productos` |
| Categorías (RF03): CRUD, no se elimina si tiene productos | Hecho | `/categorias` |
| Proveedores (RF04): CRUD con búsqueda y baja lógica | Hecho | `/proveedores` |
| Clientes (RF05): listar, buscar, crear, editar y eliminar (sin ventas) | Hecho | `/clientes` |
| Ventas (RF06): carrito, control de stock, anulación con motivo | Hecho | `/ventas/nueva`, `/ventas` |
| Compras (RF07): registro con costo editable, anulación que revierte el stock | Hecho | `/compras`, `/compras/nueva` |
| Inventario (RF08): ajustes con observación e historial de movimientos por producto | Hecho | `/productos/{id}/ajuste`, `/productos/{id}/movimientos` |
| Panel estadístico (RF10): ventas del día y del mes, ticket promedio, gráfico de 7 días, más vendidos | Hecho | `/panel` |
| Reportes (RF09): ventas por día, inventario valorizado, stock bajo y más vendidos; CSV para Excel e impresión a PDF | Hecho | `/reportes` |
| Comparación de periodos (RF11): periodo A frente a B con variación % | Hecho | `/comparar-periodos` |
| Bitácora (RF12): registro automático (Observer), ingreso y salida, consulta con filtros | Hecho | `/bitacora` |

## Instalación con XAMPP

1. Crear la base `microempresa_db` en phpMyAdmin (utf8mb4).
2. Copiar `.env.example` a `.env` y configurar:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=microempresa_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Ejecutar:
   ```
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve
   ```
4. Abrir http://localhost:8000

Usuarios de prueba (creados por el seeder): `admin@microempresa.test` y `vendedor@microempresa.test`, contraseña `password`.

El script SQL equivalente está en `database/sql/microempresa_db.sql`.

### Sesión de 15 minutos (RNF05)

La sesión se cierra tras 15 minutos de inactividad: `SESSION_LIFETIME=15` en `.env.example` y valor por defecto 15 en `config/session.php`.
Si ya tienes un `.env` creado antes de este cambio, edita ahí `SESSION_LIFETIME` (o bórralo para usar el valor por defecto) y ejecuta `php artisan config:clear`.

## Pruebas

```
php artisan test
```
