<?php

namespace App\Http\Controllers;

use App\Imports\ImportadorSyscol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    /**
     * Importación masiva ETL sincrónica: procesa uno o varios Excels en esta
     * misma petición, con transacción por archivo. El importador por lotes lo
     * deja en segundos: sin cola ni worker que vigilar.
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivos_excel' => 'required|array|min:1|max:1',
            'archivos_excel.*' => 'file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $resultados = [];
        $loteId = (string) Str::uuid();
        $totalNuevas = 0;
        $totalOmitidas = 0;

        foreach ($request->file('archivos_excel') as $archivo) {
            $resultados[] = $this->importarUno($archivo, $loteId, $totalNuevas, $totalOmitidas);
        }

        return back()
            ->with('resumenImportacion', $resultados)
            ->with('success', 'Importación: '.count($resultados).' archivo(s), '.$totalNuevas.' calificaciones nuevas, '.$totalOmitidas.' omitidas por repetidas.');
    }

    /**
     * Estado en vivo de las últimas importaciones (lo consulta la vista cada 2s).
     * Acepta ?limite (1-100, default 20) para el "mostrar más" del historial.
     */
    public function estado(Request $request)
    {
        $limite = min(100, max(1, (int) $request->query('limite', 20)));

        $filas = DB::table('importacion_excels as i')
            ->leftJoin('cursos as c', 'c.id', '=', 'i.curso_id')
            ->orderByDesc('i.iniciadaEn')
            ->limit($limite)
            ->get([
                'i.id',
                'i.lote_id as lote',
                'i.archivoOriginal as archivo',
                'i.estadoImportacion as estado',
                'i.filasProcesadas as insertadas',
                'i.filasRechazadas as omitidas',
                'i.filas_totales as total',
                'i.anio',
                'i.iniciadaEn as iniciada_en',
                'i.finalizadaEn as finalizada_en',
                'i.error',
                'c.nivel',
                'c.letra',
            ]);

        return response()->json($filas);
    }

    /**
     * @return array{archivo: string, estado: string, insertadas: int, omitidas: int, mensaje: string}
     */
    private function importarUno(mixed $archivo, string $loteId, int &$totalNuevas, int &$totalOmitidas): array
    {
        $nombre = $archivo->getClientOriginalName();
        $hash = hash_file('sha256', $archivo->getRealPath());

        // La verdad es la DATA, no el historial: si el hash coincide pero los
        // datos ya no están (borrado manual o limpieza), se jubilan las filas
        // obsoletas y el archivo entra normal. Solo es repetido si sus datos
        // siguen presentes.
        [$valido, $total, $motivo, $anoArchivo, $gradoArchivo, $letraArchivo] = $this->revisarFormato($archivo->getRealPath());
        if (! $valido) {
            return [
                'archivo' => $nombre,
                'estado' => 'error',
                'insertadas' => 0,
                'omitidas' => 0,
                'mensaje' => 'Formato inválido: '.$motivo,
            ];
        }

        $previas = DB::table('importacion_excels')
            ->where('checksum', $hash)
            ->whereIn('estadoImportacion', ['EN_COLA', 'PROCESANDO', 'PROCESADA'])
            ->pluck('id')
            ->all();

        if ($previas !== [] && $this->datosPresentes($gradoArchivo, $letraArchivo, $anoArchivo)) {
            DB::table('importacion_excels')->insert([
                'id' => (string) Str::uuid(),
                'lote_id' => $loteId,
                'archivoOriginal' => $nombre,
                'checksum' => null,
                'iniciadaEn' => now(),
                'finalizadaEn' => now(),
                'estadoImportacion' => 'REPETIDO',
                'filasProcesadas' => 0,
                'filasRechazadas' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'archivo' => $nombre,
                'estado' => 'repetido',
                'insertadas' => 0,
                'omitidas' => 0,
                'mensaje' => 'Ya estaba subido. Repetido: 0 cambios en total.',
            ];
        }

        // Filas obsoletas (historial sin datos): se jubilan para no bloquear.
        if ($previas !== []) {
            DB::table('importacion_excels')->whereIn('id', $previas)->delete();
        }

        // Sincrónico: se procesa en esta misma petición (el importador por
        // lotes lo deja en segundos, sin worker que vigilar).
        $importacionId = (string) Str::uuid();
        DB::table('importacion_excels')->insert([
            'id' => $importacionId,
            'lote_id' => $loteId,
            'archivoOriginal' => $nombre,
            'checksum' => $hash,
            'iniciadaEn' => now(),
            'estadoImportacion' => 'PROCESANDO',
            'filasProcesadas' => 0,
            'filasRechazadas' => 0,
            'filas_totales' => $total,
            'anio' => $anoArchivo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::beginTransaction();

            $importador = new ImportadorSyscol;
            $importador->calificaciones->anio = $anoArchivo;
            Excel::import($importador, $archivo);

            $insertadas = $importador->calificaciones->insertadas;
            $omitidas = $importador->calificaciones->omitidas;

            // El archivo es de un curso: se registra para la matriz de cobertura.
            $cursoId = null;
            if ($importador->nomina->grado !== null && $importador->nomina->letra !== null) {
                $cursoId = DB::table('cursos')
                    ->where('nivel', $importador->nomina->grado)
                    ->where('letra', $importador->nomina->letra)
                    ->where('anioEscolar', $anoArchivo)
                    ->value('id');
            }

            DB::table('importacion_excels')->where('id', $importacionId)->update([
                'estadoImportacion' => 'PROCESADA',
                'finalizadaEn' => now(),
                'filasProcesadas' => $insertadas,
                'filasRechazadas' => $omitidas,
                'curso_id' => $cursoId,
                'updated_at' => now(),
            ]);

            DB::commit();

            $totalNuevas += $insertadas;
            $totalOmitidas += $omitidas;

            return [
                'archivo' => $nombre,
                'estado' => 'ok',
                'insertadas' => $insertadas,
                'omitidas' => $omitidas,
                'mensaje' => "{$insertadas} nuevas, {$omitidas} omitidas por repetidas.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('importacion_excels')->where('id', $importacionId)->update([
                'estadoImportacion' => 'ERROR',
                'finalizadaEn' => now(),
                'error' => mb_substr($e->getMessage(), 0, 1000),
                'updated_at' => now(),
            ]);

            return [
                'archivo' => $nombre,
                'estado' => 'error',
                'insertadas' => 0,
                'omitidas' => 0,
                'mensaje' => 'Error al procesar: '.$e->getMessage(),
            ];
        }
    }

    /**
     * ¿Siguen presentes los datos de un archivo? El curso de su primera fila
     * existe y tiene calificaciones. Sin esto, un borrado manual dejaría al
     * historial bloqueando la re-subida para siempre.
     */
    private function datosPresentes(?string $grado, ?string $letra, ?int $ano): bool
    {
        if ($grado === null || $letra === null || $ano === null) {
            return true;
        }

        $cursoId = DB::table('cursos')
            ->where('nivel', $grado)
            ->where('letra', $letra)
            ->where('anioEscolar', $ano)
            ->value('id');

        if ($cursoId === null) {
            return false;
        }

        return DB::table('calificaciones')
            ->join('matriculas', 'calificaciones.matricula_id', '=', 'matriculas.id')
            ->where('matriculas.curso_id', $cursoId)
            ->exists();
    }

    /**
     * Revisión rápida sin tocar la DB: hojas NOMINA+CALIFICACIONES con los
     * headers v1.0. Frena el "formato del amigo" antes de importar.
     *
     * @return array{bool, int, string, ?int, ?string, ?string}
     */
    private function revisarFormato(string $ruta): array
    {
        try {
            $libro = IOFactory::load($ruta);
        } catch (\Throwable) {
            return [false, 0, 'no se pudo leer como Excel.', null, null, null];
        }

        $hojas = $libro->getSheetNames();
        if (! in_array('NOMINA', $hojas, true) || ! in_array('CALIFICACIONES', $hojas, true)) {
            return [false, 0, 'debe tener las hojas NOMINA y CALIFICACIONES.', null, null, null];
        }

        $nomina = $libro->getSheetByName('NOMINA')->rangeToArray('A1:K1')[0];
        $esperadaNomina = ['ANO', 'COD_ENSENANZA', 'GRADO', 'CURSO', 'RUN_TOKEN', 'NOMBRES',
            'APELLIDOS', 'FECHA_INCORPORACION', 'FECHA_RETIRO',
            'ASISTENCIA_PCT', 'TELEFONO_APODERADO'];
        if ($nomina !== $esperadaNomina) {
            return [false, 0, 'la hoja NOMINA no tiene las 11 columnas v1.0.', null, null, null];
        }

        $hojaNotas = $libro->getSheetByName('CALIFICACIONES');
        $calif = $hojaNotas->rangeToArray('A1:L1')[0];
        $esperadaCalif = ['ANO', 'COD_ENSENANZA', 'GRADO', 'CURSO', 'RUN_TOKEN',
            'COD_SUBSECTOR', 'SUBSECTOR', 'INCIDE_PROMOCION',
            'NOTA_FINAL', 'NOTA_CONCEPTUAL', 'EXIMIDO', 'SEMESTRE'];
        if ($calif !== $esperadaCalif) {
            return [false, 0, 'la hoja CALIFICACIONES no tiene las 12 columnas v1.0.', null, null, null];
        }

        $primera = $libro->getSheetByName('NOMINA')->rangeToArray('A2:D2')[0];
        $ano = is_numeric($primera[0] ?? null) ? (int) $primera[0] : null;
        $grado = trim((string) ($primera[2] ?? ''));
        $letra = trim((string) ($primera[3] ?? ''));

        return [true, max(0, $hojaNotas->getHighestRow() - 1), '', $ano, $grado !== '' ? $grado : null, $letra !== '' ? $letra : null];
    }

    /**
     * Elimina un curso completo (solo desarrollo): notas, matrículas,
     * pivotes ramo-curso, alumnos huérfanos y el curso. El historial de
     * importaciones se conserva (curso_id pasa a nulo).
     */
    public function destroyCurso(Request $request)
    {
        if (! $request->user()->hasRole('Administrador')) {
            abort(403, 'Solo el Administrador puede eliminar cursos.');
        }

        $datos = $request->validate([
            'nivel' => 'required|integer|min:1|max:4',
            'letra' => 'required|string|in:A,B,C',
            'anio' => 'required|integer|min:2000|max:2100',
        ]);

        $curso = DB::table('cursos')
            ->where('nivel', (string) $datos['nivel'])
            ->where('letra', $datos['letra'])
            ->where('anioEscolar', $datos['anio'])
            ->first();

        if (! $curso) {
            return back()->withErrors(['curso' => 'El curso no existe o ya fue eliminado.']);
        }

        DB::beginTransaction();

        try {
            $matriculasIds = DB::table('matriculas')->where('curso_id', $curso->id)->pluck('id');

            $notas = DB::table('calificaciones')->whereIn('matricula_id', $matriculasIds)->delete();
            $matriculas = DB::table('matriculas')->where('curso_id', $curso->id)->delete();
            DB::table('asignatura_curso')->where('curso_id', $curso->id)->delete();
            $alumnos = DB::table('estudiantes')->whereNotIn('id', DB::table('matriculas')->select('estudiante_id'))->delete();
            // El historial de este curso describe datos que dejan de existir:
            // se elimina para no bloquear una futura re-subida por hash.
            DB::table('importacion_excels')->where('curso_id', $curso->id)->delete();
            DB::table('cursos')->where('id', $curso->id)->delete();

            DB::commit();

            return back()->with('success', "Curso {$datos['nivel']}°{$datos['letra']} {$datos['anio']} eliminado: {$matriculas} alumnos, {$notas} notas.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['curso' => 'Error al eliminar: '.$e->getMessage()]);
        }
    }
}
