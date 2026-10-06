# INDG-16 / HUF-02 — Generación Automatizada de Datos Sintéticos

> Estado: implementado · Rama `dev` · Sprint 1: Cimientos y Datos · Equipo 3

## De qué trata

Como no se pueden usar datos reales de menores, esta historia crea la "fábrica
de datos de prueba": un comando que genera Excels réplica SIGE (NOMINA +
CALIFICACIONES) indistinguibles en forma de los que exporta el colegio.
Es el insumo de todo lo demás: alimenta al ETL (INDG-15), al motor de riesgo
(INDG-20) y a los dashboards (INDG-22/23).

## Qué se hizo

- Comando `sape:generar-sinteticos {grado} {letra} {ano} --alumnos --seed`
  → `storage/app/sinteticos/nomina_calificaciones_{ANO}_{GRADO}{LETRA}.xlsx`.
- Catálogos por grado según currículum real (1°-2° plan común; 3°-4° HC con
  6 común + 1 electivo + 3 diferenciados sorteados de 18).
- 4 bordes forzados (retirado, cuello MAT, conceptual REL, EX EFI) + rasgo 4° medio.
- Matriz generada: 48 archivos, 1.920 alumnos, 32.640 notas (2023–2026, A/B/C).
- Test 6/6 en verde; suite total 31/31 contra Postgres.

## Decisiones

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| D1 | PhpSpreadsheet directo | maatwebsite/excel | Sin facades/config; basta para 2 hojas |
| D2 | PHPUnit (existente) | Instalar Pest ahora | Evita scope creep; Pest queda a futuro según stack |
| D3 | Faker pasa a `require` | Dejarlo en `require-dev` | El generador debe correr en demos del VPS (`--no-dev`) |
| D4 | Salida en `storage/app/sinteticos/` | Commitear xlsx de ejemplo | `storage/` está ignorado; no contamina el repo |
| D5 | PHP 8.3 estándar del equipo | PHP 8.2 mínimo del composer | Rango `^8.2` lo permite; unifica las 3 máquinas |
| D6 | Seed fijo reproducible (`--seed`) | Aleatorio puro | Demos y tests deterministas (CA-5) |
| D7 | Catálogos por grado (1°-2° plan común; 3°-4° HC con 6+1+3) | Un solo catálogo para todo | Currículum real: DS 1264/2016 (1°-2°), DS 193/2019 (3°-4°); diferenciado de 27 ministeriales, oferta de 18 |
| D8 | Electivo Capa 1 + 3 diferenciados aleatorios por alumno (seed) | Diferenciados fijos para todos | Reproduce electividad real; determinista por seed |
| D9 | Rasgo 4° medio: asistencia sem2 menor (año recortado por PAES) | Trato idéntico a otros grados | Cierre actas 4° ~20 nov, PAES 30 nov–2 dic (Mineduc 2026) |
| D10 | Notas parciales múltiples por celda (5-6 S1, 2-3 S2; conceptual/EX en 1 fila) + RUN por bloque de seed (22000000 + seed*100) | 1 fila por celda + RUN 19999xxx | Chatbot y paneles necesitan notas visibles por subsector; bloques separan RUN entre cursos (sin colisión). Tests leen hoja completa (rango fijo falseaba el verde). Conocido: 22M cae en rango RUN real → mitigado con nombres/teléfonos ficticios; futuro: bloque 30M+ claramente sintético |

## Estado de criterios de aceptación

CA-1 ✅ · CA-2 ✅ (ETL idempotente por hash, E2E 3/3) · CA-3 ✅ · CA-4 ✅ · CA-5 ✅.

## Historial de cambios

| Fecha | Cambio | Autor |
|---|---|---|
| 25-09-2026 | Creación del plan y decisiones iniciales | Equipo 3 |
| 25-09-2026 | Implementación: comando `sape:generar-sinteticos`, PhpSpreadsheet 3.10.8, Faker a `require`. Test 4/4 verde (340 assertions). Suite total 29/29. Nota: se requirió `ext-gd` en php.ini (manual) | Equipo 3 |
| 26-09-2026 | Catálogos por grado + electivos aleatorios. Matriz 4 grados × A,B,C × 2023–2026 × 40 = 48 archivos (1.920 alumnos, 32.640 notas, ~1,6 MB en storage/). Test 6/6 (442 assertions), suite total 31/31 | Equipo 3 |
| 04-10-2026 | CA-2 cerrada: ETL idempotente por hash SHA-256 (`importacion_excels.checksum` unique) + rollback total. E2E HTTP 3/3 en `sape_test`. Generador revertido al contrato tras ruptura de `main` | Equipo 3 |
| 06-10-2026 | D10 aceptada (notas parciales + RUN por bloque). Tests a hoja completa: 6/6 (2167 assertions), suite 31/31 | Equipo 3 |
