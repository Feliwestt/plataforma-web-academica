<?php

namespace Tests\Unit;

use App\Services\PrivacidadService;
use PHPUnit\Framework\TestCase;

/**
 * INDG-27 / HUNF-02 — Privacidad y Tokenización (sin BD).
 * Nombres 100% ficticios, inventados para el test.
 */
class PrivacidadServiceTest extends TestCase
{
    private PrivacidadService $servicio;

    private array $alumnos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new PrivacidadService;
        $this->alumnos = [
            ['nombres' => 'Javiera Ignacia', 'apellidos' => 'Muñoz Rojas'],
            ['nombres' => 'Benjamín Andrés', 'apellidos' => 'Soto Paredes'],
        ];
    }

    /** CA-1: ningún nombre llega al texto anonimizado. */
    public function test_anonimiza_nombres_completos(): void
    {
        ['texto' => $texto, 'mapa' => $mapa] = $this->servicio->anonimizar(
            'Javiera Muñoz va mal en MAT y Benjamín Soto falta mucho.',
            $this->alumnos
        );

        foreach (['Javiera', 'Muñoz', 'Benjamín', 'Soto'] as $nombre) {
            $this->assertStringNotContainsString($nombre, $texto);
        }
        $this->assertCount(2, $mapa);
    }

    /** Mismo alumno → mismo token; distintos → distintos. */
    public function test_tokens_estables_y_distintos(): void
    {
        ['texto' => $texto] = $this->servicio->anonimizar(
            'Javiera primero, Javiera después y Benjamín al final.',
            $this->alumnos
        );

        $this->assertSame(2, substr_count($texto, '[ALUMNO_1]'));
        $this->assertSame(1, substr_count($texto, '[ALUMNO_2]'));
    }

    /** Texto sin nombres queda inalterado y el mapa vacío. */
    public function test_sin_nombres_no_toca_nada(): void
    {
        $original = 'El curso tiene buen promedio general en ciencias.';

        ['texto' => $texto, 'mapa' => $mapa] = $this->servicio->anonimizar($original, $this->alumnos);

        $this->assertSame($original, $texto);
        $this->assertSame([], $mapa);
    }

    /** Apellido suelto de palabra común no se reemplaza. */
    public function test_apellido_suelto_comun_no_se_toca(): void
    {
        ['texto' => $texto] = $this->servicio->anonimizar(
            'El soto del colegio es grande y las paredes están pintadas.',
            $this->alumnos
        );

        $this->assertStringContainsString('soto', $texto);
        $this->assertStringContainsString('paredes', $texto);
    }

    /** CA-2: restaurar devuelve los nombres reales. */
    public function test_restaurar_devuelve_original(): void
    {
        $original = 'Javiera Muñoz necesita apoyo y Benjamín Soto también.';
        ['texto' => $anonimo, 'mapa' => $mapa] = $this->servicio->anonimizar($original, $this->alumnos);
        $respuestaIA = 'Recomiendo para '.$anonimo.' un plan lector.';

        $this->assertSame(
            'Recomiendo para Javiera Ignacia Muñoz Rojas necesita apoyo y Benjamín Andrés Soto Paredes también. un plan lector.',
            $this->servicio->restaurar($respuestaIA, $mapa)
        );
    }

    /** Restaurar con mapa vacío no altera nada. */
    public function test_restaurar_mapa_vacio(): void
    {
        $this->assertSame('Hola mundo.', $this->servicio->restaurar('Hola mundo.', []));
    }
}
