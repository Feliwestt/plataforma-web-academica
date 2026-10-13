<?php

namespace Tests\Feature;

use App\Imports\ImportadorSyscol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Anti-duplicados lógicos: reimportar el mismo contenido (aunque el archivo
 * tenga distinto hash) no duplica calificaciones. La fecha queda fuera de la
 * huella y es determinista por semestre.
 */
class ImportacionIdempotenteTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archivo = tempnam(sys_get_temp_dir(), 'sape_dup').'.xlsx';
        $this->artisan('sape:generar-sinteticos', [
            'grado' => '1', 'letra' => 'B', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => '1601', '--salida' => $this->archivo,
        ])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        parent::tearDown();
    }

    public function test_reimportacion_mismo_contenido_no_duplica(): void
    {
        $primera = new ImportadorSyscol;
        Excel::import($primera, $this->archivo);
        $total = DB::table('calificaciones')->count();
        $this->assertGreaterThan(0, $total);
        $this->assertSame($total, $primera->calificaciones->insertadas);
        $this->assertSame(0, $primera->calificaciones->omitidas);

        // Segunda pasada con el mismo contenido (simula archivo "casi igual":
        // el hash de archivo difiere pero las filas son las mismas).
        $segunda = new ImportadorSyscol;
        Excel::import($segunda, $this->archivo);
        $this->assertSame($total, DB::table('calificaciones')->count());
        $this->assertSame(0, $segunda->calificaciones->insertadas);
        $this->assertSame($total, $segunda->calificaciones->omitidas);
    }

    public function test_fecha_determinista_por_semestre(): void
    {
        Excel::import(new ImportadorSyscol, $this->archivo);
        $ano = now()->year;

        $fechas = DB::table('calificaciones')->distinct()->pluck('fecha')->all();
        sort($fechas);
        $this->assertSame([$ano.'-06-15', $ano.'-10-15'], $fechas);

        $this->assertSame(
            [$ano.'-06-15'],
            DB::table('calificaciones')->where('semestre', 1)->distinct()->pluck('fecha')->all()
        );
        $this->assertSame(
            [$ano.'-10-15'],
            DB::table('calificaciones')->where('semestre', 2)->distinct()->pluck('fecha')->all()
        );
    }
}
