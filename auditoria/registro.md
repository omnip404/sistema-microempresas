# Registro del loop de seguridad

`N | AAAA-MM-DD HH:MM | Sxx | candidatos C / confirmados K / corregidos F | pruebas aprobadas/total | commits | nota`

1 | 2026-11-01 00:00 | S00 | candidatos 0 / confirmados 0 / corregidos 0 | 81/81 | c55ffa2 | arquitectura.md creada
2 | 2026-11-01 00:00 | S01 | candidatos 2 / confirmados 2 / corregidos 2 | 83/83 | a0caaaa | 3 notas; se añadió la suite Security a phpunit.xml
3 | 2026-11-01 00:00 | S02 | candidatos 4 / confirmados 3 / corregidos 3 | 86/86 | dd47168 | 1 rechazado, 6 notas; tests/TestCase.php reinicia la sesión en cada actingAs
4 | 2026-11-01 00:00 | S03 | candidatos 0 / confirmados 0 / corregidos 0 | 86/86 | - | 3 notas (comodines LIKE, buscar[] da 500, rango de fechas sin tope)
5 | 2026-11-01 00:00 | S04 | candidatos 0 / confirmados 0 / corregidos 0 | 86/86 | - | 2 notas (CDN sin SRI, sin CSP); sin vectores de XSS
6 | 2026-11-01 00:00 | S05 | candidatos 0 / confirmados 0 / corregidos 0 | 86/86 | - | 2 notas nuevas (cookie secure/same_site, DELETE productos sin UI); CSRF completo
7 | 2026-11-01 00:00 | S06 | candidatos 2 / confirmados 2 / corregidos 2 | 91/91 | 87637ce | 8 notas; SEC-04 absorbida por SEC-24
8 | 2026-11-01 00:00 | S07 | candidatos 2 / confirmados 2 / corregidos 2 | 93/93 | 93a6921 | 5 notas; SEC-03 absorbida por SEC-33/34
9 | 2026-11-01 00:00 | S08 | candidatos 2 / confirmados 2 / corregidos 2 | 95/95 | ca73100 | 2 notas; fórmulas CSV y escape de fputcsv
10 | 2026-11-01 00:00 | S09 | candidatos 1 / confirmados 1 / corregidos 1 | 97/97 | beb8b18 | 3 recomendaciones (cabeceras, .env.example/README, DocumentRoot), 5 notas
11 | 2026-11-01 00:00 | S10 | candidatos 1 / confirmados 1 / corregidos 0 | 97/97 (1 omitida a propósito) | - | SEC-52 pendiente (SRI necesita internet); composer audit sin ejecutar
