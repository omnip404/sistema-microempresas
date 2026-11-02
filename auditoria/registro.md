# Registro del loop de seguridad

`N | AAAA-MM-DD HH:MM | Sxx | candidatos C / confirmados K / corregidos F | pruebas aprobadas/total | commits | nota`

1 | 2026-11-01 00:00 | S00 | candidatos 0 / confirmados 0 / corregidos 0 | 81/81 | c55ffa2 | arquitectura.md creada
2 | 2026-11-01 00:00 | S01 | candidatos 2 / confirmados 2 / corregidos 2 | 83/83 | a0caaaa | 3 notas; se añadió la suite Security a phpunit.xml
3 | 2026-11-01 00:00 | S02 | candidatos 4 / confirmados 3 / corregidos 3 | 86/86 | dd47168 | 1 rechazado, 6 notas; tests/TestCase.php reinicia la sesión en cada actingAs
4 | 2026-11-01 00:00 | S03 | candidatos 0 / confirmados 0 / corregidos 0 | 86/86 | - | 3 notas (comodines LIKE, buscar[] da 500, rango de fechas sin tope)
5 | 2026-11-01 00:00 | S04 | candidatos 0 / confirmados 0 / corregidos 0 | 86/86 | - | 2 notas (CDN sin SRI, sin CSP); sin vectores de XSS
6 | 2026-11-01 00:00 | S05 | candidatos 0 / confirmados 0 / corregidos 0 | 86/86 | - | 2 notas nuevas (cookie secure/same_site, DELETE productos sin UI); CSRF completo
