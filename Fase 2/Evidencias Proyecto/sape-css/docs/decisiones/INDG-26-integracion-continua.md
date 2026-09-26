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
  pasos Pint `--test` → `php artisan test` → `npm ci && npm run build`.
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
31/31 en Postgres); primera corrida real en GitHub pendiente al primer push
a `dev`. Regla acordada: CI en `dev`, `main` protegida por PR (sin push directo).

## Historial de cambios

| Fecha | Cambio | Autor |
|---|---|---|
| 26-09-2026 | Creación del workflow CI + migración de tests a Postgres + decisiones CI-1–CI-6 | Equipo 3 |
