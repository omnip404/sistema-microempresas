# Hallazgos de seguridad

Estados: CONFIRMADO (con prueba) · RECHAZADO (con la protección que lo impide) · NECESITA_VALIDACION
(con el dato exacto que falta) · NOTA (endurecimiento, no es vulnerabilidad) · RECOMENDACIÓN (no se corrige en el loop).
Nivel = probabilidad × impacto (1–3 cada uno): 1–2 bajo · 3–4 medio · 6–9 alto.

| ID | Unidad | Título | OWASP 2025 | Rol atacante | P | I | Nivel | Estado | Prueba | Commit |
|---|---|---|---|---|---|---|---|---|---|---|
| SEC-01 | S01 | Un Administrador puede quitarse el rol (o degradar al último admin) y dejar el sistema sin administradores | A01 | Administrador | 2 | 2 | 4 medio | CONFIRMADO, corregido | tests/Security/Sec01aTest.php | a0caaaa |
| SEC-02 | S01 | Una cuenta desactivada conserva su sesión abierta y sigue operando (solo se verifica `activo` en el login) | A07 | Vendedor desactivado | 2 | 2 | 4 medio | CONFIRMADO, corregido (middleware `activo`) | tests/Security/Sec01bTest.php | a0caaaa |
| SEC-03 | S01 | `VentaService::anular`/`CompraService::anular` comprueban el estado antes de la transacción y sin `lockForUpdate`: dos anulaciones simultáneas devolverían el stock dos veces | A06 | Administrador | 1 | 2 | 2 bajo | NOTA (se revisa a fondo en S07) | - | - |
| SEC-04 | S01 | `VentaController::store` valida `exists:productos,id` sin exigir `activo=true`; con una petición manipulada se venden productos desactivados | A06 | Vendedor | 2 | 1 | 2 bajo | NOTA (se revisa a fondo en S06) | - | - |
| SEC-05 | S01 | Sin Policies/Gates ni `authorize()` real en los FormRequest: todo el control depende del grupo de rutas con `rol:` | A01 | - | 1 | 2 | 2 bajo | NOTA (defensa en profundidad) | - | - |
