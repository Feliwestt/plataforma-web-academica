<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class NominaSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // 0. Ignorar filas totalmente vacías
            if (empty($row['run_token']) || empty($row['nombres'])) {
                continue;
            }

            $now = now();
            
            // Limpieza básica de texto
            $grado = trim((string) $row['grado']);
            $cursoLetra = trim((string) $row['curso']);
            $runToken = trim((string) $row['run_token']);
            $telefonoApoderado = !empty($row['telefono_apoderado']) ? trim((string) $row['telefono_apoderado']) : null;

            // 1. Buscar o crear el Curso
            $curso = DB::table('cursos')
                ->where('nivel', $grado)
                ->where('letra', $cursoLetra)
                ->first();

            $cursoId = $curso ? $curso->id : (string) Str::uuid();
            
            if (!$curso) {
                DB::table('cursos')->insert([
                    'id' => $cursoId,
                    'nivel' => $grado,
                    'letra' => $cursoLetra,
                    'anioEscolar' => $now->year,
                    'created_at' => $now, 
                    'updated_at' => $now
                ]);
            }

            // 2. Buscar o crear el Apoderado
            $apoderadoId = null;
            if ($telefonoApoderado) {
                $apoderado = DB::table('apoderados')->where('telefono', $telefonoApoderado)->first();
                $apoderadoId = $apoderado ? $apoderado->id : (string) Str::uuid();

                if (!$apoderado) {
                    DB::table('apoderados')->insert([
                        'id' => $apoderadoId,
                        'nombre' => 'Apoderado no registrado',
                        'telefono' => $telefonoApoderado,
                        'created_at' => $now, 
                        'updated_at' => $now
                    ]);
                }
            }

            // 3. Buscar o crear el Estudiante
            $estudiante = DB::table('estudiantes')->where('identificadorInterno', $runToken)->first();
            $estudianteId = $estudiante ? $estudiante->id : (string) Str::uuid();

            if (!$estudiante) {
                DB::table('estudiantes')->insert([
                    'id' => $estudianteId,
                    'nombres' => trim($row['nombres']),
                    'apellidos' => trim($row['apellidos']),
                    'identificadorInterno' => $runToken,
                    'created_at' => $now, 
                    'updated_at' => $now
                ]);
            }

            // 4. Conectar todo en la Matrícula
            $matriculaExistente = DB::table('matriculas')
                ->where('estudiante_id', $estudianteId)
                ->where('curso_id', $cursoId)
                ->first();

            if (!$matriculaExistente) {
                // Formatear correctamente la fecha desde Excel
                $fechaIngreso = $now->toDateString();
                if (!empty($row['fecha_incorporacion'])) {
                    if (is_numeric($row['fecha_incorporacion'])) {
                        $fechaIngreso = Carbon::instance(ExcelDate::excelToDateTimeObject($row['fecha_incorporacion']))->toDateString();
                    } else {
                        $fechaIngreso = Carbon::parse($row['fecha_incorporacion'])->toDateString();
                    }
                }

                DB::table('matriculas')->insert([
                    'id' => (string) Str::uuid(),
                    'estudiante_id' => $estudianteId,
                    'curso_id' => $cursoId,
                    'apoderado_id' => $apoderadoId,
                    'fechaIngreso' => $fechaIngreso,
                    'estado' => 'ACTIVO',
                    'created_at' => $now, 
                    'updated_at' => $now
                ]);
            }
        }
    }
}