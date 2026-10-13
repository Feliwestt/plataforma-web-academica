<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CoberturaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Importación masiva ETL por cola (vista importar del dashboard, rol
 * Administrador): se encolan varios archivos, se procesan de a uno y la
 * vista sigue el estado en vivo. En tests la cola es sync: el trabajo corre
 * dentro del POST.
 */
class ImportacionMasivaTest extends TestCase
{
    use RefreshDatabase;

    private string $archivoA;

    private string $archivoA2;

    private string $archivoB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archivoA = tempnam(sys_get_temp_dir(), 'sape_m1').'.xlsx';
        $this->archivoB = tempnam(sys_get_temp_dir(), 'sape_m2').'.xlsx';
        $this->artisan('sape:generar-sinteticos', [
            'grado' => '1', 'letra' => 'B', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => '1601', '--salida' => $this->archivoA,
        ])->assertSuccessful();
        $this->artisan('sape:generar-sinteticos', [
            'grado' => '1', 'letra' => 'C', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => '1602', '--salida' => $this->archivoB,
        ])->assertSuccessful();
        // Copia byte-idéntica: re-subir el MISMO archivo (no regenerado).
        $this->archivoA2 = tempnam(sys_get_temp_dir(), 'sape_m1c').'.xlsx';
        copy($this->archivoA, $this->archivoA2);
    }

    protected function tearDown(): void
    {
        @unlink($this->archivoA);
        @unlink($this->archivoA2);
        @unlink($this->archivoB);
        parent::tearDown();
    }

    private function comoArchivo(string $ruta, string $nombre): UploadedFile
    {
        return new UploadedFile(
            $ruta, $nombre,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null, true
        );
    }

    public function test_subida_procesa_el_archivo_en_la_peticion(): void
    {
        $respuesta = $this->actingAs(User::factory()->create())->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );

        $respuesta->assertRedirect();
        $resumen = session('resumenImportacion');
        $this->assertCount(1, $resumen);
        $this->assertSame('ok', $resumen[0]['estado']);
        $this->assertGreaterThan(0, $resumen[0]['insertadas']);

        $this->assertSame(1, DB::table('importacion_excels')->where('estadoImportacion', 'PROCESADA')->count());
        // La importación queda ligada a su curso para la matriz de cobertura.
        $this->assertSame(0, DB::table('importacion_excels')->whereNull('curso_id')->count());

        // El lote identifica la subida para agrupar el historial.
        $lotes = DB::table('importacion_excels')->where('estadoImportacion', 'PROCESADA')->distinct()->pluck('lote_id');
        $this->assertCount(1, $lotes);
        $this->assertNotNull($lotes[0]);
    }

    public function test_mas_de_un_archivo_se_rechaza(): void
    {
        $this->actingAs(User::factory()->create())->post(
            route('admin.importar.store'),
            ['archivos_excel' => [
                $this->comoArchivo($this->archivoA, 'curso_1b.xlsx'),
                $this->comoArchivo($this->archivoB, 'curso_1c.xlsx'),
            ]]
        )->assertSessionHasErrors('archivos_excel');

        $this->assertSame(0, DB::table('importacion_excels')->count());
        $this->assertSame(0, DB::table('calificaciones')->count());
    }

    public function test_subidas_secuenciales_y_repetido_no_duplica(): void
    {
        $user = User::factory()->create();
        $uno = ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]];
        $otro = ['archivos_excel' => [$this->comoArchivo($this->archivoB, 'curso_1c.xlsx')]];

        $this->actingAs($user)->post(route('admin.importar.store'), $uno);
        $this->actingAs($user)->post(route('admin.importar.store'), $otro);

        $this->assertSame(2, DB::table('importacion_excels')->where('estadoImportacion', 'PROCESADA')->count());
        $this->assertSame(0, DB::table('importacion_excels')->whereNull('curso_id')->count());
        $total = DB::table('calificaciones')->count();
        $this->assertGreaterThan(0, $total);

        // Re-subir los mismos archivos no duplica (hash idéntico por archivo),
        // pero el intento queda registrado en el historial como REPETIDO.
        $this->actingAs($user)->post(route('admin.importar.store'), $uno);
        $this->actingAs($user)->post(route('admin.importar.store'), $otro);
        $resumen2 = session('resumenImportacion');
        $this->assertSame('repetido', $resumen2[0]['estado']);
        $this->assertSame($total, DB::table('calificaciones')->count());
        $this->assertSame(2, DB::table('importacion_excels')->where('estadoImportacion', 'REPETIDO')->count());
    }

    public function test_formato_invalido_se_rechaza_al_encolar(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'sape_malo').'.xlsx';
        (new Xlsx(new Spreadsheet))->save($ruta);

        try {
            $this->actingAs(User::factory()->create())->post(
                route('admin.importar.store'),
                ['archivos_excel' => [$this->comoArchivo($ruta, 'malo.xlsx')]]
            );
        } finally {
            @unlink($ruta);
        }

        $resumen = session('resumenImportacion');
        $this->assertCount(1, $resumen);
        $this->assertSame('error', $resumen[0]['estado']);
        $this->assertSame(0, DB::table('importacion_excels')->count());
        $this->assertSame(0, DB::table('calificaciones')->count());
    }

    public function test_estado_devuelve_la_cola_en_vivo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoB, 'curso_1c.xlsx')]]
        );

        $respuesta = $this->actingAs($user)->getJson(route('admin.importaciones.estado'));
        $respuesta->assertOk();
        $filas = $respuesta->json();
        $this->assertCount(2, $filas);
        $archivos = array_column($filas, 'archivo');
        sort($archivos);
        $this->assertSame(['curso_1b.xlsx', 'curso_1c.xlsx'], $archivos);
        $this->assertSame('PROCESADA', $filas[0]['estado']);
        $this->assertGreaterThan(0, $filas[0]['insertadas']);
        $this->assertArrayHasKey('id', $filas[0]);
        $this->assertNotNull($filas[0]['lote']);
        $this->assertNotEmpty($filas[0]['iniciada_en']);

        // ?limite recorta para el "mostrar más" del historial.
        $acotada = $this->actingAs($user)->getJson(route('admin.importaciones.estado', ['limite' => 1]));
        $acotada->assertOk();
        $this->assertCount(1, $acotada->json());
    }

    public function test_matriz_cobertura_marca_subidos_y_faltantes(): void
    {
        $this->artisan('sape:generar-sinteticos', [
            'grado' => '2', 'letra' => 'A', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => '1601', '--salida' => $this->archivoA,
        ])->assertSuccessful();

        $this->actingAs(User::factory()->create())->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_2a.xlsx')]]
        );

        $matriz = app(CoberturaService::class)->matriz();
        $this->assertCount(12, $matriz);

        $porCurso = [];
        foreach ($matriz as $celda) {
            $porCurso[$celda['nivel'].$celda['letra']] = $celda;
        }

        $this->assertSame('subido', $porCurso['2A']['estado']);
        $this->assertSame(10, $porCurso['2A']['alumnos']);
        $this->assertGreaterThan(0, $porCurso['2A']['notas']);

        $this->assertSame('no_subido', $porCurso['4C']['estado']);
        $this->assertSame(0, $porCurso['4C']['alumnos']);
        $this->assertSame(0, $porCurso['4C']['notas']);
    }

    public function test_multianual_mismo_curso_distinto_ano_no_se_mezcla(): void
    {
        // Mismo seed = mismos RUNs: el alumno avanza de 1B 2023 a 1B 2026.
        $this->artisan('sape:generar-sinteticos', [
            'grado' => '1', 'letra' => 'B', 'ano' => '2023',
            '--alumnos' => '10', '--seed' => '1601', '--salida' => $this->archivoA,
        ])->assertSuccessful();

        $user = User::factory()->create();
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b_2023.xlsx')]]
        );
        $notas2023 = DB::table('calificaciones')->count();
        $this->assertGreaterThan(0, $notas2023);

        $this->artisan('sape:generar-sinteticos', [
            'grado' => '1', 'letra' => 'B', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => '1601', '--salida' => $this->archivoA,
        ])->assertSuccessful();
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b_2026.xlsx')]]
        );

        // Dos cursos distintos, mismos 10 estudiantes con 2 matrículas.
        $this->assertSame(2, DB::table('cursos')->where('nivel', '1')->where('letra', 'B')->count());
        $this->assertSame(10, DB::table('estudiantes')->count());
        $this->assertSame(20, DB::table('matriculas')->count());
        // El año distingue la huella: el 2026 entra completo, sin omitidas.
        $this->assertSame(2 * $notas2023, DB::table('calificaciones')->count());

        // Fechas con el año de cada archivo, no el de hoy.
        $fechas = DB::table('calificaciones')->distinct()->orderBy('fecha')->pluck('fecha')->all();
        $this->assertSame(['2023-06-15', '2023-10-15', '2026-06-15', '2026-10-15'], $fechas);

        // Matriz por año: 1B subido en ambos, con 10 alumnos cada uno.
        foreach ([2023, 2026] as $anio) {
            $celda = null;
            foreach (app(CoberturaService::class)->matriz($anio) as $c) {
                if ($c['nivel'] === 1 && $c['letra'] === 'B') {
                    $celda = $c;
                }
            }
            $this->assertNotNull($celda);
            $this->assertSame('subido', $celda['estado']);
            $this->assertSame(10, $celda['alumnos']);
        }
    }

    public function test_resubir_despues_de_borrado_manual_entra_limpio(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );
        $notas = DB::table('calificaciones')->count();
        $this->assertGreaterThan(0, $notas);

        // Borrado manual fuera del sistema: datos fuera, historial intacto.
        $cursoId = DB::table('cursos')->where('nivel', '1')->where('letra', 'B')->value('id');
        $mids = DB::table('matriculas')->where('curso_id', $cursoId)->pluck('id');
        DB::table('calificaciones')->whereIn('matricula_id', $mids)->delete();
        DB::table('matriculas')->where('curso_id', $cursoId)->delete();
        DB::table('asignatura_curso')->where('curso_id', $cursoId)->delete();
        DB::table('estudiantes')->whereNotIn('id', DB::table('matriculas')->select('estudiante_id'))->delete();
        DB::table('cursos')->where('id', $cursoId)->delete();
        $this->assertSame(0, DB::table('calificaciones')->count());

        // La re-subida verifica la DATA (ausente): jubila la fila obsoleta y entra.
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA2, 'curso_1b.xlsx')]]
        );

        $resumen = session('resumenImportacion');
        $this->assertSame('ok', $resumen[0]['estado']);
        $this->assertSame($notas, DB::table('calificaciones')->count());
        $this->assertSame(1, DB::table('importacion_excels')->where('estadoImportacion', 'PROCESADA')->count());
    }

    public function test_fila_legacy_sin_curso_con_datos_presentes_sigue_repetido(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );
        $notas = DB::table('calificaciones')->count();

        // Simula fila de antes del campo curso_id: sin curso pero con datos.
        DB::table('importacion_excels')->update(['curso_id' => null]);

        $this->actingAs($user)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA2, 'curso_1b.xlsx')]]
        );

        $resumen = session('resumenImportacion');
        $this->assertSame('repetido', $resumen[0]['estado']);
        $this->assertSame($notas, DB::table('calificaciones')->count());
    }

    public function test_dashboard_admin_recibe_cobertura(): void
    {
        Role::create(['name' => 'Administrador']);
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('cobertura'));
    }

    public function test_eliminar_curso_borra_solo_su_curso(): void
    {
        Role::create(['name' => 'Administrador']);
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );
        $this->actingAs($admin)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoB, 'curso_1c.xlsx')]]
        );
        $notasAntes = DB::table('calificaciones')->count();
        $alumnosAntes = DB::table('estudiantes')->count();

        $this->actingAs($admin)
            ->delete(route('admin.cursos.destroy'), ['nivel' => 1, 'letra' => 'B', 'anio' => 2026])
            ->assertRedirect();

        // 1B fuera por completo, 1C intacto, historial del curso eliminado.
        $this->assertSame(0, DB::table('cursos')->where('nivel', '1')->where('letra', 'B')->count());
        $this->assertSame(1, DB::table('cursos')->where('nivel', '1')->where('letra', 'C')->count());
        $this->assertSame($alumnosAntes - 10, DB::table('estudiantes')->count());
        $this->assertLessThan($notasAntes, DB::table('calificaciones')->count());
        $this->assertSame(1, DB::table('importacion_excels')->where('estadoImportacion', 'PROCESADA')->count());

        $celda = null;
        foreach (app(CoberturaService::class)->matriz(2026) as $c) {
            if ($c['nivel'] === 1 && $c['letra'] === 'B') {
                $celda = $c;
            }
        }
        $this->assertSame('no_subido', $celda['estado']);

        // Re-subir el curso borrado entra limpio (el historial no lo bloquea).
        $this->artisan('sape:generar-sinteticos', [
            'grado' => '1', 'letra' => 'B', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => '1601', '--salida' => $this->archivoA,
        ])->assertSuccessful();
        $this->actingAs($admin)->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );
        $resumen = session('resumenImportacion');
        $this->assertSame('ok', $resumen[0]['estado']);
        $this->assertSame($notasAntes, DB::table('calificaciones')->count());
    }

    public function test_eliminar_curso_requiere_admin(): void
    {
        $this->actingAs(User::factory()->create())->post(
            route('admin.importar.store'),
            ['archivos_excel' => [$this->comoArchivo($this->archivoA, 'curso_1b.xlsx')]]
        );
        $notas = DB::table('calificaciones')->count();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.cursos.destroy'), ['nivel' => 1, 'letra' => 'B', 'anio' => 2026])
            ->assertForbidden();
        $this->assertSame($notas, DB::table('calificaciones')->count());
        $this->assertSame(1, DB::table('cursos')->where('nivel', '1')->where('letra', 'B')->count());
    }

    public function test_eliminar_curso_inexistente_no_rompe_nada(): void
    {
        Role::create(['name' => 'Administrador']);
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->delete(route('admin.cursos.destroy'), ['nivel' => 4, 'letra' => 'C', 'anio' => 2026])
            ->assertRedirect();
        $this->assertSame(0, DB::table('cursos')->count());
    }
}
