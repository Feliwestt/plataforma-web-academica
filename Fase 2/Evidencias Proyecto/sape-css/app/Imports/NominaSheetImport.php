<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NominaSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $now = now();

            // 1. Buscar o crear el Curso
            $curso = DB::table('cursos')->where('nivel', $row['grado'])->where('letra', $row['curso'])->first();
            $cursoId = $curso ? $curso->id : (string) Str::uuid();
            if (!$curso) {
                DB::table('cursos')->insert([
                    'id' => $cursoId,
                    'nivel' => (string) $row['grado'],
                    'letra' => (string) $row['curso'],
                    'anioEscolar' => 2026, // O el año correspondiente
                    'created_at' => $now, 'updated_at' => $now
                ]);
            }

            // 2. Buscar o crear el Apoderado (Si hay teléfono)
            $apoderadoId = null;
            if (!empty($row['telefono_apoderado'])) {
                $apoderado = DB::table('apoderados')->where('telefono', $row['telefono_apoderado'])->first();
                $apoderadoId = $apoderado ? $apoderado->id : (string) Str::uuid();
                if (!$apoderado) {
                    DB::table('apoderados')->insert([
                        'id' => $apoderadoId,
                        'nombre' => 'Apoderado no registrado', // El Excel no trae el nombre, solo el teléfono
                        'telefono' => $row['telefono_apoderado'],
                        'created_at' => $now, 'updated_at' => $now
                    ]);
                }
            }

            // 3. Buscar o crear el Estudiante (por RUN minimizado)
            $estudiante = DB::table('estudiantes')->where('identificadorInterno', $row['run_token'])->first();
            $estudianteId = $estudiante ? $estudiante->id : (string) Str::uuid();
            if (!$estudiante) {
                DB::table('estudiantes')->insert([
                    'id' => $estudianteId,
                    'nombres' => $row['nombres'],
                    'apellidos' => $row['apellidos'],
                    'identificadorInterno' => $row['run_token'],
                    'created_at' => $now, 'updated_at' => $now
                ]);
            }

            // 4. Conectar todo en la Matrícula
            $matriculaExistente = DB::table('matriculas')
                ->where('estudiante_id', $estudianteId)
                ->where('curso_id', $cursoId)
                ->first();

            if (!$matriculaExistente) {
                DB::table('matriculas')->insert([
                    'id' => (string) Str::uuid(),
                    'estudiante_id' => $estudianteId,
                    'curso_id' => $cursoId,
                    'apoderado_id' => $apoderadoId,
                    'fechaIngreso' => $row['fecha_incorporacion'] ?? $now->toDateString(),
                    'estado' => 'ACTIVO',
                    'created_at' => $now, 'updated_at' => $now
                ]);
            }
        }
    }
}