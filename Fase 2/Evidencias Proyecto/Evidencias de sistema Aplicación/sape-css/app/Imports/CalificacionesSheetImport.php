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

    /** Ocurrencias vistas por huella base dentro de esta importación. */
    private array $vistos = [];

    public function collection(Collection $rows): void
    {
        $now = now();
        $anoActual = $now->year;

        foreach ($rows as $row) {
            // Validar regla de negocio de eximición
            $tieneNota = ! empty($row['nota_final']);
            $tieneConceptual = ! empty($row['nota_conceptual']);
            $esEximido = (strtoupper($row['eximido'] ?? '') === 'EX');

            if ((int) $tieneNota + (int) $tieneConceptual + (int) $esEximido > 1) {
                throw new Exception("Error en RUN {$row['run_token']}: Un alumno no puede tener nota numérica, conceptual y estar eximido simultáneamente.");
            }

            // Huella lógica de la fila (sin fecha: el Excel no trae fecha por
            // nota y el importador usa una fija por semestre). El sufijo de
            // ocurrencia distingue parciales del mismo archivo con idéntico
            // valor (cada fila es una evaluación); en una reimportación del
            // mismo contenido los sufijos coinciden y todo se salta.
            $base = implode('|', [
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

            if (DB::table('calificaciones')->where('row_hash', $huella)->exists()) {
                $this->omitidas++;

                continue;
            }

            // 1. Buscar la Asignatura
            $asignatura = DB::table('asignaturas')->where('codigo', $row['cod_subsector'])->first();
            $asignaturaId = $asignatura ? $asignatura->id : (string) Str::uuid();
            if (! $asignatura) {
                DB::table('asignaturas')->insert([
                    'id' => $asignaturaId,
                    'codigo' => $row['cod_subsector'],
                    'nombre' => $row['subsector'],
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // 2. Buscar al estudiante
            $estudiante = DB::table('estudiantes')->where('identificadorInterno', $row['run_token'])->first();
            if (! $estudiante) {
                continue;
            }

            $matricula = DB::table('matriculas')->where('estudiante_id', $estudiante->id)->first();
            if (! $matricula) {
                continue;
            }

            // 3. Conectar Asignatura con el Curso
            $asigCurso = DB::table('asignatura_curso')
                ->where('asignatura_id', $asignaturaId)
                ->where('curso_id', $matricula->curso_id)
                ->first();

            $asigCursoId = $asigCurso ? $asigCurso->id : (string) Str::uuid();
            if (! $asigCurso) {
                DB::table('asignatura_curso')->insert([
                    'id' => $asigCursoId,
                    'asignatura_id' => $asignaturaId,
                    'curso_id' => $matricula->curso_id,
                    'ponderacion' => 1.00,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // 4. Transformar la Calificación
            $notaCruda = str_replace(',', '.', (string) ($row['nota_final'] ?? ''));
            $valorDecimal = is_numeric($notaCruda) ? (float) $notaCruda : 0.0;
            $evaluacion = $esEximido ? 'EXIMIDO' : ($row['nota_conceptual'] ?? 'REGULAR');

            // --- Fecha determinista según semestre (el Excel no trae fecha
            // por nota): 15-06 para S1 y 15-10 para S2. Nada de azar, para
            // que la misma fila siempre deje la misma fecha.
            $semestreExcel = (int) ($row['semestre'] ?? 1);

            $fechaNota = $semestreExcel === 1
                ? Carbon::create($anoActual, 6, 15)
                : Carbon::create($anoActual, 10, 15);

            // 5. Insertar en la base de datos
            DB::table('calificaciones')->insert([
                'id' => (string) Str::uuid(),
                'matricula_id' => $matricula->id,
                'asignatura_curso_id' => $asigCursoId,
                'valor' => $valorDecimal,
                'ponderacion' => 1.00,
                'evaluacion' => $evaluacion,
                'semestre' => $semestreExcel,
                'fecha' => $fechaNota,
                'row_hash' => $huella,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $this->insertadas++;
        }
    }
}
