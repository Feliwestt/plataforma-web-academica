<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CalificacionesSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $now = now();

            // Validar regla de negocio de eximición
            $tieneNota = !empty($row['nota_final']);
            $tieneConceptual = !empty($row['nota_conceptual']);
            $esEximido = (strtoupper($row['eximido'] ?? '') === 'EX');

            if ((int)$tieneNota + (int)$tieneConceptual + (int)$esEximido > 1) {
                throw new Exception("Error en RUN {$row['run_token']}: Un alumno no puede tener nota numérica, conceptual y estar eximido simultáneamente.");
            }

            // 1. Buscar la Asignatura
            $asignatura = DB::table('asignaturas')->where('codigo', $row['cod_subsector'])->first();
            $asignaturaId = $asignatura ? $asignatura->id : (string) Str::uuid();
            if (!$asignatura) {
                DB::table('asignaturas')->insert([
                    'id' => $asignaturaId,
                    'codigo' => $row['cod_subsector'],
                    'nombre' => $row['subsector'],
                    'created_at' => $now, 'updated_at' => $now
                ]);
            }

            // 2. Buscar al estudiante para obtener su matrícula actual
            $estudiante = DB::table('estudiantes')->where('identificadorInterno', $row['run_token'])->first();
            if (!$estudiante) continue; // Si no existe el estudiante en nómina, saltamos la nota

            $matricula = DB::table('matriculas')->where('estudiante_id', $estudiante->id)->first();
            if (!$matricula) continue;

            // 3. Conectar Asignatura con el Curso (AsignaturaCurso)
            $asigCurso = DB::table('asignatura_curso')
                ->where('asignatura_id', $asignaturaId)
                ->where('curso_id', $matricula->curso_id)
                ->first();
            
            $asigCursoId = $asigCurso ? $asigCurso->id : (string) Str::uuid();
            if (!$asigCurso) {
                DB::table('asignatura_curso')->insert([
                    'id' => $asigCursoId,
                    'asignatura_id' => $asignaturaId,
                    'curso_id' => $matricula->curso_id,
                    'ponderacion' => 1.00,
                    'created_at' => $now, 'updated_at' => $now
                ]);
            }

            // 4. Insertar la Calificación
            // Reemplazamos la coma por un punto para que PHP lo reconozca como decimal
            $notaCruda = str_replace(',', '.', (string) ($row['nota_final'] ?? ''));
            
            // Ahora sí lo validamos y convertimos a decimal
            $valorDecimal = is_numeric($notaCruda) ? (float) $notaCruda : 0.0;
            
            $evaluacion = $esEximido ? 'EXIMIDO' : ($row['nota_conceptual'] ?? 'REGULAR');

            DB::table('calificaciones')->insert([
                'id' => (string) Str::uuid(),
                'matricula_id' => $matricula->id,
                'asignatura_curso_id' => $asigCursoId,
                'valor' => $valorDecimal,
                'ponderacion' => 1.00,
                'evaluacion' => $evaluacion,
                'fecha' => clone $now,
                'created_at' => $now, 'updated_at' => $now
            ]);
        }
    }
}