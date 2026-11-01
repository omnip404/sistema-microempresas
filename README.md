# Sistema Web de Control y Gestión de Microempresas

Proyecto de la materia Ingeniería de Software (UPEA, paralelo 7toC). Laravel 12 · PHP 8.2 · MySQL 8 · Bootstrap 5 · Chart.js.

## Avance (Fase II, sprint 1–2)

| Módulo | Estado |
|---|---|
| Seguridad: inicio de sesión, bloqueo tras 3 intentos (5 min), roles Administrador/Vendedor | Hecho |
| Catálogo: productos (alta, edición, baja lógica, búsqueda y filtro) | Hecho |
| Ventas: registro con carrito, control de stock, anulación con motivo | Hecho |
| Inventario: movimientos de entrada/salida y alertas de stock bajo | Hecho |
| Panel estadístico: ventas del día y del mes, ticket promedio, gráfico de 7 días, más vendidos | Hecho |
| Bitácora automática (Observer) | Hecho |
| Compras, proveedores, clientes (pantallas), reportes PDF/Excel, comparación de periodos | Pendiente (sprints 3–4) |

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

## Pruebas

```
php artisan test
```
