<?php

namespace App\Imports;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CalificacionesSheetImport implements ToCollection, WithHeadingRow
{
    public int $insertadas = 0;

    public int $omitidas = 0;

    /** Año del archivo (lo fija quien importa desde la revisión previa). */
    public ?int $anio = null;

    /** Ocurrencias vistas por huella base dentro de esta importación. */
    private array $vistos = [];

    private const LOTE = 500;

    public function collection(Collection $rows): void
    {
        $now = now();
        $anoActual = $now->year;

        // Mapas precargados: unas pocas consultas en vez de ~6 por fila.
        $runs = [];
        $codigos = [];
        foreach ($rows as $row) {
            if (! empty($row['run_token'])) {
                $runs[trim((string) $row['run_token'])] = true;
            }
            if (! empty($row['cod_subsector'])) {
                $cod = strtoupper(trim((string) $row['cod_subsector']));
                $codigos[$cod] ??= trim((string) ($row['subsector'] ?? ''));
            }
        }

        $estudiantes = DB::table('estudiantes')
            ->whereIn('identificadorInterno', array_keys($runs))
            ->pluck('id', 'identificadorInterno')
            ->all();

        $matriculas = [];
        if ($estudiantes !== []) {
            foreach (DB::table('matriculas')->whereIn('estudiante_id', array_values($estudiantes))->get(['id', 'estudiante_id', 'curso_id']) as $m) {
                $matriculas[$m->estudiante_id][$m->curso_id] = $m->id;
            }
        }

        $cursos = [];
        foreach (DB::table('cursos')->get(['id', 'nivel', 'letra', 'anioEscolar']) as $c) {
            $cursos[$c->nivel.'|'.$c->letra.'|'.$c->anioEscolar] = $c->id;
        }

        $asignaturas = DB::table('asignaturas')
            ->whereIn('codigo', array_keys($codigos))
            ->pluck('id', 'codigo')
            ->all();
        $nuevasAsig = [];
        foreach ($codigos as $cod => $nombre) {
            if (! isset($asignaturas[$cod])) {
                $asignaturas[$cod] = (string) Str::uuid();
                $nuevasAsig[] = [
                    'id' => $asignaturas[$cod],
                    'codigo' => $cod,
                    'nombre' => $nombre !== '' ? $nombre : $cod,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        if ($nuevasAsig !== []) {
            DB::table('asignaturas')->insert($nuevasAsig);
        }

        $pivotes = [];
        $cursoIds = array_values(array_unique($cursos));
        if ($cursoIds !== []) {
            foreach (DB::table('asignatura_curso')->whereIn('curso_id', $cursoIds)->get(['id', 'asignatura_id', 'curso_id']) as $p) {
                $pivotes[$p->asignatura_id.'|'.$p->curso_id] = $p->id;
            }
        }

        // Huellas ya guardadas: la reimportación "casi igual" se salta en RAM.
        $huellas = DB::table('calificaciones')->whereNotNull('row_hash')->pluck('row_hash')->flip()->all();

        $lote = [];

        foreach ($rows as $row) {
            // Validar regla de negocio de eximición
            $tieneNota = ! empty($row['nota_final']);
            $tieneConceptual = ! empty($row['nota_conceptual']);
            $esEximido = (strtoupper($row['eximido'] ?? '') === 'EX');

            if ((int) $tieneNota + (int) $tieneConceptual + (int) $esEximido > 1) {
                throw new Exception("Error en RUN {$row['run_token']}: Un alumno no puede tener nota numérica, conceptual y estar eximido simultáneamente.");
            }

            // Año de la fila (del Excel, no de hoy): distingue 2023 de 2026.
            $anioFila = is_numeric($row['ano'] ?? null)
                ? (int) $row['ano']
                : ($this->anio ?? $anoActual);

            // Huella lógica de la fila (sin fecha: el Excel no trae fecha por
            // nota y el importador usa una fija por semestre). El sufijo de
            // ocurrencia distingue parciales del mismo archivo con idéntico
            // valor (cada fila es una evaluación); en una reimportación del
            // mismo contenido los sufijos coinciden y todo se salta.
            $base = implode('|', [
                $anioFila,
                trim((string) ($row['run_token'] ?? '')),
                strtoupper(trim((string) ($row['cod_subsector'] ?? ''))),
                (int) ($row['semestre'] ?? 1),
                str_replace(',', '.', trim((string) ($row['nota_final'] ?? ''))),
                strtoupper(trim((string) ($row['nota_conceptual'] ?? ''))),
                strtoupper(trim((string) ($row['eximido'] ?? ''))),
            ]);
            $ocurrencia = ($this->vistos[$base] ?? 0) + 1;
            $this->vistos[$base] = $ocurrencia;
            $huella = hash('sha256', $base.'|'.$ocurrencia);

            if (isset($huellas[$huella])) {
                $this->omitidas++;

                continue;
            }

            // Estudiante y SU matrícula en el curso de ESTA fila
            // (un alumno puede tener matrículas en varios años/cursos).
            $estudianteId = $estudiantes[trim((string) ($row['run_token'] ?? ''))] ?? null;
            if ($estudianteId === null) {
                continue;
            }

            $cursoId = $cursos[trim((string) ($row['grado'] ?? '')).'|'.trim((string) ($row['curso'] ?? '')).'|'.$anioFila] ?? null;
            if ($cursoId === null) {
                continue;
            }

            $matriculaId = $matriculas[$estudianteId][$cursoId] ?? null;
            if ($matriculaId === null) {
                continue;
            }

            $asignaturaId = $asignaturas[strtoupper(trim((string) ($row['cod_subsector'] ?? '')))] ?? null;
            if ($asignaturaId === null) {
                continue;
            }

            $clavePivote = $asignaturaId.'|'.$cursoId;
            if (! isset($pivotes[$clavePivote])) {
                $pivotes[$clavePivote] = (string) Str::uuid();
                DB::table('asignatura_curso')->insert([
                    'id' => $pivotes[$clavePivote],
                    'asignatura_id' => $asignaturaId,
                    'curso_id' => $cursoId,
                    'ponderacion' => 1.00,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $asigCursoId = $pivotes[$clavePivote];

            // 4. Transformar la Calificación
            $notaCruda = str_replace(',', '.', (string) ($row['nota_final'] ?? ''));
            $valorDecimal = is_numeric($notaCruda) ? (float) $notaCruda : 0.0;
            $evaluacion = $esEximido ? 'EXIMIDO' : ($row['nota_conceptual'] ?? 'REGULAR');

            // --- Fecha determinista según semestre y año del archivo (el Excel
            // no trae fecha por nota): 15-06 para S1 y 15-10 para S2. Nada de
            // azar, para que la misma fila siempre deje la misma fecha.
            $semestreExcel = (int) ($row['semestre'] ?? 1);

            $fechaNota = $semestreExcel === 1
                ? Carbon::create($anioFila, 6, 15)
                : Carbon::create($anioFila, 10, 15);

            $lote[] = [
                'id' => (string) Str::uuid(),
                'matricula_id' => $matriculaId,
                'asignatura_curso_id' => $asigCursoId,
                'valor' => $valorDecimal,
                'ponderacion' => 1.00,
                'evaluacion' => $evaluacion,
                'semestre' => $semestreExcel,
                'fecha' => $fechaNota,
                'row_hash' => $huella,
                'created_at' => $now, 'updated_at' => $now,
            ];
            $huellas[$huella] = true;

            if (count($lote) >= self::LOTE) {
                DB::table('calificaciones')->insert($lote);
                $this->insertadas += count($lote);
                $lote = [];
            }
        }

        if ($lote !== []) {
            DB::table('calificaciones')->insert($lote);
            $this->insertadas += count($lote);
        }
    }
}
