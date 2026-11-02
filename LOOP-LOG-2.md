# Registro del loop 2

Una línea por iteración (la escribe el loop):
`N | AAAA-MM-DD HH:MM | Lxx | HECHA / BLOQUEADA / SIN TAREAS / LÍNEA BASE ROTA | pruebas aprobadas/total | commit | nota breve`

(Columna commit: el hash no puede ir dentro de su propio commit; se escribe «git log Lxx» y el hash real está en `git log --oneline`.)

1 | 2026-11-01 20:58 | L13 | HECHA | 95/95 | git log «L13» | Comprobante imprimible, enlace tras vender y en listado; 6 pruebas nuevas
2 | 2026-11-01 21:00 | L14 | HECHA | 101/101 | git log «L14» | Reporte «Compras por periodo» con filtro de proveedor y CSV; 6 pruebas nuevas
3 | 2026-11-01 21:02 | L15 | HECHA | 105/105 | git log «L15» | Encabezado solo-impresión (negocio, reporte, periodo, emisión) y @page con counter(page); 4 pruebas nuevas
4 | 2026-11-01 21:05 | L16 | HECHA | 111/111 | git log «L16» | Ganancia estimada del mes en el panel (reemplaza «venta promedio»); consultas del panel movidas a ReporteService; 6 pruebas nuevas
5 | 2026-11-01 21:08 | L17 | HECHA | 118/118 | git log «L17» | Panel: líneas por día del periodo, barras top 10, filtro desde/hasta (PeriodoRequest) y tablas alternativas; 7 pruebas nuevas
6 | 2026-11-01 21:11 | L18 | HECHA | 123/123 | git log «L18» | Dona de ventas por categoría (top 5 + «Otras») con leyenda de valores y tabla; paleta validada con validate_palette.js (PASS, contraste bajo → leyenda y tabla como relieve); 5 pruebas nuevas
7 | 2026-11-01 21:14 | L19 | HECHA | 127/127 | git log «L19» | SESSION_LIFETIME=15 en .env.example y por defecto en config/session.php; nota en README; 4 pruebas. OJO: el .env local no se tocó (regla); sigue con su valor anterior
8 | 2026-11-01 21:17 | L20 | HECHA | 131/131 | git log «L20» | RendimientoSeeder (10 000 ventas) + RendimientoTest (@group rendimiento). Tiempos (SQLite en memoria, límite 3 s): panel mes 0,068 s · panel 12 meses 0,127 s · rep. ventas 0,066 s · más vendidos 0,100 s · compras 0,025 s · inventario 0,015 s · stock bajo 0,007 s · CSV ventas 0,058 s · comparar 0,011 s · listado ventas 0,015 s. Sin índices nuevos. Falta medir en MySQL real
9 | 2026-11-01 21:20 | L21 | HECHA | 137/137 | git log «L21» | Comando respaldo:crear (SQLite copia; MySQL mysqldump con clave por entorno), programado daily() en routes/console.php, README con cron/Programador de tareas; 6 pruebas. mysqldump solo probado con Process::fake (no hay MySQL aquí)
