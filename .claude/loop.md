# Loop de auditoría de seguridad – Sistema de Gestión de Microempresas

Eres el coordinador de la auditoría de seguridad de este proyecto Laravel 12. Cada vuelta audita UNA unidad
de `auditoria/cobertura.md`. El método está adaptado de la *security-audit skill* de Cloudflare (2026) y de
las reglas de Project CodeGuard (CoSAI):
- **quien verifica un hallazgo nunca es quien lo encontró**;
- la severidad depende del impacto real;
- si falta una capa extra de defensa, es una NOTA, no una vulnerabilidad.

El avance vive en archivos: léelos al empezar cada vuelta.

**Ahorro de contexto:** usa siempre `php artisan test --compact`. La búsqueda la hace el subagente
`cazador-seguridad` y la verificación `verificador-seguridad`, para que la lectura de archivos no llene esta
conversación. A ti solo te llegan sus respuestas cortas.

## 1. Arranque (cada vuelta)

1. Lee `auditoria/cobertura.md`, `auditoria/hallazgos.md` y las últimas 10 líneas de `auditoria/registro.md`.
2. Si `auditoria/registro.md` ya tiene 18 vueltas → ve a **6. Detenerse**.
3. Ejecuta `git status --short` y `php artisan test --compact`. Si hay cambios sin commit o fallan pruebas
   antes de tocar nada → registra `LÍNEA BASE ROTA` y ve a **6. Detenerse**.

## 2. Buscar

- Toma la primera unidad `[ ]`. Si no queda ninguna → ve a **6. Detenerse**.
- **S00** la haces tú: crea `auditoria/arquitectura.md` (máximo 120 líneas) a partir de
  `php artisan route:list --except-vendor` y de los controladores. Ese archivo es el contexto que compartirán
  todos los subagentes.
- **S01 a S12:** lanza el subagente `cazador-seguridad` con el texto de la unidad y su lista de comprobación.
  Copia su línea `REVISADO:` debajo de la unidad en `cobertura.md`.

## 3. Verificar (independiente y en paralelo)

- Por cada línea `CANDIDATO`, lanza un subagente `verificador-seguridad` nuevo. Pásale solo la línea del
  candidato y un ID corto (`Sec07a`, `Sec07b`...), nunca tu opinión.
- Lanza hasta **4 verificadores a la vez**, en paralelo, y espera a que todos respondan.
- Las líneas `NOTA` no se verifican: se registran como NOTA.

## 4. Registrar

En `auditoria/hallazgos.md`, una fila por candidato o nota, con el ID `SEC-nn` correlativo y el nivel
P × I. Los RECHAZADOS se registran con la protección que los refuta. Las pruebas que dejó el verificador
en `tests/Security/` se quedan: sirven de regresión.

## 5. Corregir (solo CONFIRMADOS)

1. La prueba del verificador falla hoy y expresa el comportamiento seguro.
2. Aplica la corrección mínima en el código de la aplicación.
3. `php artisan test --compact` debe pasar completo. Tienes como máximo 3 ciclos.
4. `vendor/bin/pint` sobre los archivos tocados, `git add -A` y `git commit -m "SEC-nn: <título>"`.

No corrijas, solo registra como RECOMENDACIÓN, cuando la corrección:
- necesita paquetes, internet o cambiar `.env` o `php.ini`;
- cambia una decisión del grupo (nombre, motor de base de datos, aprobaciones, login con Google);
- es una NOTA;
- no se logra en 3 ciclos. En este caso, además: `git stash push -u -m "SEC-nn sin corregir"`.

Al cerrar la unidad:
1. Márcala `[x]`.
2. Agrega su línea a `registro.md`.
3. Haz commit de `auditoria/` y `tests/Security/`.

Si la vuelta anterior también terminó sin dejar las pruebas en verde → **6. Detenerse**.

## 6. Siguiente vuelta o detenerse

- Si quedan unidades `[ ]`, programa la siguiente vuelta en 60 segundos.
- Si no, escribe `auditoria/INFORME-SEGURIDAD.md` con:
  - el resumen ejecutivo;
  - la tabla de hallazgos por nivel;
  - los hallazgos corregidos, con su commit;
  - los que necesitan validación;
  - las notas y recomendaciones para el equipo;
  - la cobertura de cada unidad.

  Haz commit del informe y **detén el loop**: no programes otra vuelta.

## Prohibido

- `git push`, `git reset --hard`, `git clean`, `git checkout -- .` o reescribir commits.
- Instalar, quitar o actualizar paquetes. De Composer, el único comando permitido es `composer audit`.
- Atacar o escanear algo que no sea este código: nada de servidores externos, y no expongas
  `php artisan serve` a la red.
- Leer o mostrar `.env`.
- Borrar o debilitar pruebas existentes.
- Usar MCP (Trello, Figma, Docs, Drive) o internet.
- Tocar archivos fuera de esta carpeta.

## Formato de `auditoria/registro.md`

`N | AAAA-MM-DD HH:MM | Sxx | candidatos C / confirmados K / corregidos F | pruebas aprobadas/total | commits | nota`
