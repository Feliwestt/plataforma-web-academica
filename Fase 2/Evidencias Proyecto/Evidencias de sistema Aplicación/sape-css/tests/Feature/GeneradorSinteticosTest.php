<?php

namespace Tests\Feature;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * INDG-16 / HUF-02 — Criterios CA-1, CA-3, CA-4, CA-5.
 * (CA-2 se valida contra el ETL INDG-15 cuando exista.)
 */
class GeneradorSinteticosTest extends TestCase
{
    private string $salidaA;

    private string $salidaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salidaA = tempnam(sys_get_temp_dir(), 'sape_a').'.xlsx';
        $this->salidaB = tempnam(sys_get_temp_dir(), 'sape_b').'.xlsx';
    }

    protected function tearDown(): void
    {
        @unlink($this->salidaA);
        @unlink($this->salidaB);
        parent::tearDown();
    }

    private function generar(string $salida, int $seed = 1601, string $grado = '1'): void
    {
        $this->artisan('sape:generar-sinteticos', [
            'grado' => $grado, 'letra' => 'B', 'ano' => '2026',
            '--alumnos' => '10', '--seed' => (string) $seed, '--salida' => $salida,
        ])->assertSuccessful();
        $this->assertFileExists($salida);
    }

    /** CA-1: columnas exactas del formato v1.0 en ambas hojas. */
    public function test_headers_exactos_formato_v1(): void
    {
        $this->generar($this->salidaA);
        $libro = IOFactory::load($this->salidaA);

        $this->assertSame(
            ['ANO', 'COD_ENSENANZA', 'GRADO', 'CURSO', 'RUN_TOKEN', 'NOMBRES',
                'APELLIDOS', 'FECHA_INCORPORACION', 'FECHA_RETIRO',
                'ASISTENCIA_PCT', 'TELEFONO_APODERADO'],
            $libro->getSheetByName('NOMINA')->rangeToArray('A1:K1')[0]
        );
        $this->assertSame(
            ['ANO', 'COD_ENSENANZA', 'GRADO', 'CURSO', 'RUN_TOKEN',
                'COD_SUBSECTOR', 'SUBSECTOR', 'INCIDE_PROMOCION',
                'NOTA_FINAL', 'NOTA_CONCEPTUAL', 'EXIMIDO', 'SEMESTRE'],
            $libro->getSheetByName('CALIFICACIONES')->rangeToArray('A1:L1')[0]
        );
        // 10 alumnos x 7 subsectores x 2 semestres.
        $this->assertCount(140, $libro->getSheetByName('CALIFICACIONES')->rangeToArray('A2:L141'));
    }

    /** CA-3: al menos 1 caso de cada borde. */
    public function test_casos_borde_presentes(): void
    {
        $this->generar($this->salidaA);
        $libro = IOFactory::load($this->salidaA);
        $nomina = $libro->getSheetByName('NOMINA')->rangeToArray('A2:K11');
        $cal = $libro->getSheetByName('CALIFICACIONES')->rangeToArray('A2:L141');

        // Retirado con fecha.
        $this->assertNotEmpty(array_filter($nomina, fn ($f) => ! empty($f[8])));

        $notasMat = array_filter($cal, fn ($f) => $f[5] === 'MAT-01' && is_string($f[8]));
        $bajo40 = array_filter($notasMat, fn ($f) => (float) str_replace(',', '.', $f[8]) < 4.0);
        $this->assertGreaterThanOrEqual(2, count($bajo40), 'Cuello de botella MAT-01');

        $this->assertNotEmpty(array_filter($cal, fn ($f) => $f[5] === 'REL-01' && $f[9] !== ''));
        $this->assertNotEmpty(array_filter($cal, fn ($f) => $f[5] === 'EFI-01' && $f[10] === 'EX'));

        // Excluyentes: ninguna fila combina NOTA + CONCEPTUAL + EX.
        foreach ($cal as $f) {
            $this->assertLessThanOrEqual(1, (int) ! empty($f[8]) + (int) ! empty($f[9]) + (int) ! empty($f[10]));
        }
    }

    /** CA-4: RUN sintéticos con DV válido y teléfonos correlativos ficticios. */
    public function test_datos_verificablemente_ficticios(): void
    {
        $this->generar($this->salidaA);
        $nomina = IOFactory::load($this->salidaA)->getSheetByName('NOMINA')->rangeToArray('A2:K11');

        foreach ($nomina as $i => $f) {
            [$base, $dv] = explode('-', (string) $f[4]);
            $this->assertGreaterThanOrEqual(19999001, (int) $base, 'RUN en rango sintético');
            $this->assertSame(self::dv((int) $base), $dv, "DV válido para {$f[4]}");
            $this->assertSame('+569'.str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT), (string) $f[10]);
        }
    }

    /** CA-5: mismo seed genera el mismo contenido. */
    public function test_seed_fijo_repetible(): void
    {
        $this->generar($this->salidaA, 1601);
        $this->generar($this->salidaB, 1601);

        $a = IOFactory::load($this->salidaA);
        $b = IOFactory::load($this->salidaB);
        $rangos = ['NOMINA' => 'A1:K11', 'CALIFICACIONES' => 'A1:L141'];
        foreach ($rangos as $hoja => $rango) {
            $this->assertSame(
                $a->getSheetByName($hoja)->rangeToArray($rango),
                $b->getSheetByName($hoja)->rangeToArray($rango),
                "Contenido idéntico en {$hoja} con mismo seed"
            );
        }
    }

    /** Catálogo 3° medio: 6 común + 1 electivo + 3 diferenciados (20 filas/alumno). */
    public function test_catalogo_tercero_medio(): void
    {
        $this->generar($this->salidaA, 1601, '3');
        $cal = IOFactory::load($this->salidaA)->getSheetByName('CALIFICACIONES')->rangeToArray('A2:L201');
        $this->assertCount(200, $cal);

        $comun = ['LEN-03', 'MAT-03', 'ECI-03', 'FIL-03', 'ING-03', 'CCI-03'];
        $electivos = ['HIS-03', 'ART-03', 'EFI-03', 'REL-03'];
        $diferenciados = [
            'PAD-03', 'SFI-03', 'FPO-03', 'CHP-03', 'GTE-03', 'LDI-03', 'PES-03',
            'GEO-03', 'BIO-03', 'CSA-03', 'FIS-03', 'QUI-03', 'DAR-03', 'IMU-03',
            'CCM-03', 'PEV-03', 'CEF-03', 'AAV-03',
        ];

        $porRun = [];
        foreach ($cal as $f) {
            $porRun[$f[4]][] = $f[5];
        }
        $this->assertCount(10, $porRun);
        foreach ($porRun as $run => $cods) {
            $unicos = array_values(array_unique($cods));
            $this->assertCount(10, $unicos, "10 subsectores para {$run}");
            foreach ($comun as $c) {
                $this->assertContains($c, $unicos);
            }
            $this->assertCount(1, array_intersect($unicos, $electivos), "1 electivo para {$run}");
            $this->assertCount(3, array_intersect($unicos, $diferenciados), "3 diferenciados para {$run}");
        }

        // Bordes del nivel: MAT-03 bajo 4.0, REL-03 conceptual, EFI-03 EX.
        $this->assertNotEmpty(array_filter($cal, fn ($f) => $f[5] === 'MAT-03'
            && is_string($f[8]) && (float) str_replace(',', '.', $f[8]) < 4.0));
        $this->assertNotEmpty(array_filter($cal, fn ($f) => $f[5] === 'REL-03' && $f[9] !== ''));
        $this->assertNotEmpty(array_filter($cal, fn ($f) => $f[5] === 'EFI-03' && $f[10] === 'EX'));
    }

    /** Mismo seed → mismos diferenciados por alumno. */
    public function test_diferenciados_repetibles(): void
    {
        $this->generar($this->salidaA, 1601, '4');
        $this->generar($this->salidaB, 1601, '4');

        $a = IOFactory::load($this->salidaA)->getSheetByName('CALIFICACIONES')->rangeToArray('A2:L201');
        $b = IOFactory::load($this->salidaB)->getSheetByName('CALIFICACIONES')->rangeToArray('A2:L201');
        $cods = fn ($filas) => array_map(fn ($f) => [$f[4], $f[5], $f[11]], $filas);
        $this->assertSame($cods($a), $cods($b));
    }

    private static function dv(int $run): string
    {
        $suma = 0;
        $factor = 2;
        while ($run > 0) {
            $suma += ($run % 10) * $factor;
            $run = intdiv($run, 10);
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $resto = 11 - ($suma % 11);

        return $resto === 11 ? '0' : ($resto === 10 ? 'K' : (string) $resto);
    }
}
