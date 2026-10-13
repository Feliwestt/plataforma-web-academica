<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Matriz de cobertura de importación: niveles 1°-4° medio × letras A-C.
 * Indica por curso si ya se subió su nómina/calificaciones o falta.
 */
class CoberturaService
{
    public const NIVELES = [1, 2, 3, 4];

    public const LETRAS = ['A', 'B', 'C'];

    /**
     * @return array<int, array{nivel: int, letra: string, estado: string, alumnos: int, notas: int, archivo: ?string, importadoEn: ?string}>
     */
    public function matriz(?int $anio = null): array
    {
        $anio ??= now()->year;
        $cobertura = [];

        foreach (self::NIVELES as $nivel) {
            foreach (self::LETRAS as $letra) {
                $curso = DB::table('cursos')
                    ->where('nivel', (string) $nivel)
                    ->where('letra', $letra)
                    ->where('anioEscolar', $anio)
                    ->first();

                $alumnos = 0;
                $notas = 0;
                $archivo = null;
                $importadoEn = null;

                if ($curso) {
                    $alumnos = DB::table('matriculas')->where('curso_id', $curso->id)->count();
                    $notas = DB::table('calificaciones')
                        ->join('matriculas', 'calificaciones.matricula_id', '=', 'matriculas.id')
                        ->where('matriculas.curso_id', $curso->id)
                        ->count();

                    $ultima = DB::table('importacion_excels')
                        ->where('curso_id', $curso->id)
                        ->where('estadoImportacion', 'PROCESADA')
                        ->orderByDesc('finalizadaEn')
                        ->first(['archivoOriginal', 'finalizadaEn']);

                    if ($ultima) {
                        $archivo = $ultima->archivoOriginal;
                        $importadoEn = $ultima->finalizadaEn;
                    }
                }

                $cobertura[] = [
                    'nivel' => $nivel,
                    'letra' => $letra,
                    'anio' => $anio,
                    'estado' => $curso === null || $alumnos === 0 ? 'no_subido' : ($notas === 0 ? 'parcial' : 'subido'),
                    'alumnos' => $alumnos,
                    'notas' => $notas,
                    'archivo' => $archivo,
                    'importadoEn' => $importadoEn,
                ];
            }
        }

        return $cobertura;
    }
}
