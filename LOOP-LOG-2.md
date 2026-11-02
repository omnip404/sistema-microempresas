# Registro del loop 2

Una línea por iteración (la escribe el loop):
`N | AAAA-MM-DD HH:MM | Lxx | HECHA / BLOQUEADA / SIN TAREAS / LÍNEA BASE ROTA | pruebas aprobadas/total | commit | nota breve`

(Columna commit: el hash no puede ir dentro de su propio commit; se escribe «git log Lxx» y el hash real está en `git log --oneline`.)

1 | 2026-11-01 20:58 | L13 | HECHA | 95/95 | git log «L13» | Comprobante imprimible, enlace tras vender y en listado; 6 pruebas nuevas
2 | 2026-11-01 21:00 | L14 | HECHA | 101/101 | git log «L14» | Reporte «Compras por periodo» con filtro de proveedor y CSV; 6 pruebas nuevas
3 | 2026-11-01 21:02 | L15 | HECHA | 105/105 | git log «L15» | Encabezado solo-impresión (negocio, reporte, periodo, emisión) y @page con counter(page); 4 pruebas nuevas
