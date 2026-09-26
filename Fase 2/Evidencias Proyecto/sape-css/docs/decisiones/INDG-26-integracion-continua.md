# INDG-26 — Integración Continua (GitHub Actions)

> Estado: implementado (primera corrida en GitHub pendiente al push) · Rama `dev`
> Sprint 1: Cimientos y Datos · Equipo 3

## De qué trata

Robot que en cada push a `dev` verifica automáticamente que el código no esté
roto: revisa estilo, corre los 31 tests contra Postgres real y compila el
frontend. Si algo falla, GitHub lo marca en rojo antes del merge.
Es la red de seguridad del equipo.

## Qué se hizo

- `.github/workflows/ci.yml`: servicio `postgres:16` + PHP 8.3 + Node 22,
  pasos `npm ci && npm run build` → Pint `--test` → `php artisan test`
  (el build va antes porque los tests de vistas exigen el manifiesto de Vite).
- `phpunit.xml` migrado de SQLite a Postgres (`sape_test`); BD creada en Docker local.
- Pint aplicado (3 archivos) → estilo en verde.
- Trigger: push/PR a `dev` con filtro de paths de código (+ PRs a `main`).

## Decisiones

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| CI-1 | Tests en CI contra Postgres real | SQLite en memoria | Paridad total local/CI; exige Docker arriba |
| CI-2 | Pint en modo `--test` | Sin Pint o modo auto-fix | El CI acusa, no modifica código ajeno |
| CI-3 | Incluir build frontend | Solo backend | Sin build fallan los tests de vistas |
| CI-4 | Trigger con filtro `paths` | Correr en todo push | No gastar CI en pushes de documentos |
| CI-5 | PHP 8.3 + Node 22 fijos | Matriz de versiones | Espeja el estándar del equipo |
| CI-6 | PHPUnit actual, sin Pest | Instalar Pest ahora | La tarjeta pide automatizar, no Pest; migración queda fuera |

## Estado

Workflow creado y verificado en local (espejo exacto del CI: Pint passed,
31/31 en Postgres). ✅ Primera corrida en GitHub en verde el 26-09-2026
(run #1, commit `8d9be62`, 51 s). Regla acordada: CI en `dev`,
`main` protegida por PR (sin push directo).

## Qué verifica cada prueba (31 tests, 503 assertions)

### Generador sintético — `tests/Feature/GeneradorSinteticosTest.php` (6 tests)

| Prueba | Qué hace | Responde a |
|---|---|---|
| headers exactos formato v1 | Compara los 11 headers de NOMINA y 12 de CALIFICACIONES contra el contrato, y cuenta 140 filas (10 alumnos × 7 × 2) | CA-1 INDG-16 |
| casos borde presentes | Exige ≥1 retirado con fecha, ≥2 notas MAT bajo 4.0, 1 conceptual REL, 1 EX EFI, y que NOTA/CONCEPTUAL/EX sean excluyentes | CA-3 INDG-16 |
| datos verificablemente ficticios | Recalcula el DV de cada RUN con algoritmo independiente y exige rango 19999xxx + teléfonos +569000000XX | CA-4 INDG-16 |
| seed fijo repetible | Genera 2 veces con seed 1601 y exige matrices idénticas | CA-5 INDG-16 |
| catálogo tercero medio | Exige 200 filas con 6 común + 1 electivo + 3 diferenciados por alumno, más bordes del nivel | CA-1/CA-3 (3°-4°) |
| diferenciados repetibles | Mismo seed en 4° medio → mismos 3 diferenciados por alumno | CA-5 (electividad) |

### Base Breeze — `tests/Feature/Auth/*` + `ProfileTest` (25 tests)

| Grupo | Qué hace | Responde a |
|---|---|---|
| AuthenticationTest (7) | Login válido/inválido, logout, bloqueo, pantallas | Base INDG-24 (auth) |
| RegistrationTest (3) | Registro con datos válidos/inválidos | Base INDG-24 |
| PasswordReset/Update/Confirmation + EmailVerification (11) | Flujos de contraseña y verificación | Base INDG-24 |
| ProfileTest (4) | Ver/editar perfil, borrar cuenta | Base perfil usuario |

### Nota de cobertura honesta

Los 25 de Breeze vienen del scaffold y **no los escribimos nosotros**: validan
que la base de auth sigue sana tras nuestros cambios (red de regresión).
Nuestros 6 sí son propios y mapean 1:1 a los CA de INDG-16.

## Historial de cambios

| Fecha | Cambio | Autor |
|---|---|---|
| 26-09-2026 | Creación del workflow CI + migración de tests a Postgres + decisiones CI-1–CI-6 | Equipo 3 |
| 26-09-2026 | Primera corrida GitHub en verde (run #1, 51 s) | Equipo 3 |
| 26-09-2026 | Sección "Qué verifica cada prueba" (trazabilidad test ↔ CA) | Equipo 3 |
