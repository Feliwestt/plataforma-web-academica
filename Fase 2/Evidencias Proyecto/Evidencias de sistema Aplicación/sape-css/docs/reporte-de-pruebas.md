# Reporte de Pruebas — INDG-16 / HUF-02 (Generación Automatizada de Datos Sintéticos)

> Incluye verificación E2E de CA-2 contra el ETL de INDG-15.

## 1. Metadata

| Campo | Valor |
|---|---|
| Fecha ejecución | 04-10-2026 |
| Historia | INDG-16 / HUF-02 — Generación Automatizada de Datos Sintéticos |
| Dependencia | INDG-15 — Importación de Nómina y Calificaciones (ETL, en `Done 🎉`) |
| Rama / commit base | `dev` en `0e1f8ec` (fast-forward desde `3db2330`, = `origin/main`) + fixes locales sin push |
| Entorno | PHP 8.3.33, Node (build Vite), Postgres 16 (`sistema_escolar_db`), BD de pruebas `sape_test` |
| Tarjeta Trello | [INDG-16 en Testing](https://trello.com/c/Yt0YdFnF/26-1-indg-16-generaci%C3%B3n-automatizada-de-datos-sint%C3%A9ticos) (queda en Testing) |
| Jira | [INDG-16](https://felipecatalan.atlassian.net/browse/INDG-16) (evidencia espejada en comentario) |

## 2. Resultado global

| Suite | Resultado |
|---|---|
| `GeneradorSinteticosTest` (CA-1/3/4/5) | ✅ 6/6 (442 assertions) |
| E2E HTTP CA-2 en `sape_test` (temporal, borrado tras correr) | ✅ 3/3 (14 assertions) |
| Suite completa `php artisan test` | ✅ 31/31 (503 assertions) |
| `npm run build` | ✅ OK (23.99s) |
| `pint --test` en archivos tocados | ✅ passed |

## 3. Evidencia por criterio de aceptación

### CA-1 — Excel del curso/año con columnas exactas v1.0 ✅
- Headers NOMINA: `ANO|COD_ENSENANZA|GRADO|CURSO|RUN_TOKEN|NOMBRES|APELLIDOS|FECHA_INCORPORACION|FECHA_RETIRO|ASISTENCIA_PCT|TELEFONO_APODERADO`.
- Headers CALIFICACIONES: `ANO|COD_ENSENANZA|GRADO|CURSO|RUN_TOKEN|COD_SUBSECTOR|SUBSECTOR|INCIDE_PROMOCION|NOTA_FINAL|NOTA_CONCEPTUAL|EXIMIDO|SEMESTRE`.
- Caso verificado: 1°B 2026, 10 alumnos, seed 1601 → 10 filas NOMINA + 140 filas CALIFICACIONES.

### CA-2 — El archivo pasa el ETL con Carga Correcta Completa ✅
E2E vía `POST /admin/importar` (`ImportController::importar`) contra `sape_test`:
- Carga válida → `10` estudiantes / `10` matrículas / `140` calificaciones + 1 fila `importacion_excels` en `PROCESADA`.
- Re-carga idéntica → mensaje `ya fue importado anteriormente (hash idéntico)`, conteos sin cambios (idempotencia por SHA-256 en `importacion_excels.checksum` unique).
- Archivo inválido (NOTA_FINAL + EX combinados) → error de validación y rollback total (`0/0/0`, sin fila de importación).

### CA-3 — Al menos 1 caso de cada borde ✅
- Retirado: `19999001-2` con `FECHA_RETIRO 30-06-2026` (1).
- Cuello de botella: MAT-01 bajo 4.0 (4 filas).
- Conceptual: REL-01 con nota conceptual (20 filas).
- Eximido: EFI-01 con `EX` (1).
- Excluyentes: 0 filas combinan NOTA + CONCEPTUAL + EX.

### CA-4 — Cero datos reales ✅
- RUN sintéticos `19999001–19999010` con DV módulo 11 válido.
- Teléfonos correlativos ficticios `+56900000001…+56900000010`.

### CA-5 — Ejecución repetible con seed fijo ✅
- 2 corridas `seed 1601` → mismo `SHA-256 contenido: a94c0e03…ce5cb3e3`.
- Test `test_seed_fijo_repetible` en verde (NOMINA + CALIFICACIONES idénticos).

## 4. Hallazgos y reparaciones aplicadas (local, sin push)

1. **Regresión del generador (traída de `main`):** ignoraba la opción `--salida` (`storage_path` forzado), cambió RUN a `20000000+seed*1000` (fuera del rango `19999xxx`) y usaba `rand()` sin seed con notas parciales múltiples (rompía determinismo y conteos: 6/6 en rojo, ETL duplicaba 495→990). → Revertido al contrato (`SapeGenerarSinteticos.php` @ `3db2330`): 6/6 en verde.
2. **ETL sin idempotencia:** `CalificacionesSheetImport` insertaba siempre → la re-carga duplicaba. → Idempotencia por hash de archivo: migración `2026_10_04_120000_make_checksum_unique_in_importacion_excels_table.php` (unique en `checksum`) + chequeo/registro en `ImportController::importar` (estados `PROCESANDO`/`PROCESADA`, rollback elimina el registro pendiente).
3. **Deuda de estilo preexistente (no tocada):** `pint --test` global acusa 31 archivos de `main`; los 3 archivos de este fix quedan `passed`.

Archivos cambiados:
- `app/Console/Commands/SapeGenerarSinteticos.php` (revert al contrato)
- `app/Http/Controllers/ImportController.php` (hash idempotencia + Pint)
- `database/migrations/2026_10_04_120000_make_checksum_unique_in_importacion_excels_table.php` (nueva)

## 5. Pendiente (DoD)

- [ ] Push + PR de `dev` (local va 10 commits sobre `origin/dev`).
- [ ] Revisión de un compañero (DoD Regla 6).
- [ ] CI verde con link en la tarjeta (DoD Regla 1).
- [ ] Mover a `Done 🎉` en la daily + daily en Slack (DoD Regla 8).
- [ ] Nota: `main` commiteó `.xlsx` en `storage/app/sinteticos/` (contra D4) y eliminó `.env.example` — coordinar con el equipo.
