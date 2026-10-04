<?php

namespace App\Console\Commands;

use Faker\Factory as FakerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * INDG-16 / HUF-02 — Generación Automatizada de Datos Sintéticos.
 *
 * Genera el Excel réplica SIGE (Formato_Datos_SIGE.md v1.0):
 * 1 archivo por curso y año, hojas NOMINA + CALIFICACIONES.
 * Catálogos por grado según currículum nacional vigente:
 * 1°-2° medio plan común (DS 1264/2016); 3°-4° medio HC con plan común
 * general + 1 electivo + 3 diferenciados (DS 193/2019, D. Ex. 876/2019).
 * Todo dato es ficticio (nombres chilenos, RUN sintético con DV válido,
 * teléfonos +569000000XX). Ejecución repetible con --seed.
 */
class SapeGenerarSinteticos extends Command
{
    protected $signature = 'sape:generar-sinteticos
        {grado=1 : Grado numérico (1-4 media)}
        {letra=B : Letra del paralelo}
        {ano=2026 : Año académico}
        {--ensenanza=310 : Código enseñanza (110 básica, 310 media HC)}
        {--alumnos=35 : Cantidad de alumnos (mínimo 4 por los casos borde)}
        {--seed=1601 : Semilla para generación reproducible}
        {--salida= : Ruta de salida (defecto: storage/app/sinteticos/...)}';

    protected $description = 'Genera el Excel sintético réplica SIGE (NOMINA + CALIFICACIONES) para un curso y año';

    /** Lista base §4 Formato_Datos_SIGE.md v1.0 (rotar, sin repetir RUN). */
    private const NOMBRES = [
        'Javiera', 'Fernanda', 'Camila', 'Valentina', 'Antonia', 'Martina',
        'Florencia', 'Catalina', 'Benjamín', 'Matías', 'Vicente', 'Martín',
        'Joaquín', 'Agustín', 'Tomás', 'Diego',
    ];

    private const SEGUNDOS_NOMBRES = [
        'Ignacia', 'Andrés', 'Paz', 'Josefa', 'Trinidad', 'Emilio', 'Sofía', 'Gabriel',
    ];

    private const APELLIDOS = [
        'Muñoz', 'Rojas', 'Contreras', 'Soto', 'Paredes',
        'Núñez', 'Cifuentes', 'Araya', 'Henríquez', 'Sandoval',
    ];

    /** Plan común 1°-2° medio (bases con sufijo de grado). */
    private const PLAN_COMUN_12 = [
        ['LEN', 'Lengua y Literatura', 'S'],
        ['MAT', 'Matemática', 'S'],
        ['HIS', 'Historia, Geografía y Ciencias Sociales', 'S'],
        ['CIE', 'Ciencias Naturales', 'S'],
        ['ING', 'Idioma Extranjero: Inglés', 'S'],
        ['EFI', 'Educación Física y Salud', 'S'],
        ['REL', 'Religión', 'N'],
    ];

    /** Plan común general 3°-4° medio HC (DS 193/2019). */
    private const PLAN_COMUN_34 = [
        ['LEN', 'Lengua y Literatura', 'S'],
        ['MAT', 'Matemática', 'S'],
        ['ECI', 'Educación Ciudadana', 'S'],
        ['FIL', 'Filosofía', 'S'],
        ['ING', 'Inglés', 'S'],
        ['CCI', 'Ciencias para la Ciudadanía', 'S'],
    ];

    /** Plan común electivo 3°-4° (elige 1 por alumno). */
    private const ELECTIVOS_34 = [
        ['HIS', 'Historia, Geografía y Ciencias Sociales', 'S'],
        ['ART', 'Artes', 'S'],
        ['EFI', 'Educación Física y Salud', 'S'],
        ['REL', 'Religión', 'N'],
    ];

    /** Plan diferenciado 3°-4° (oferta del liceo; el alumno cursa 3). */
    private const DIFERENCIADOS = [
        ['PAD', 'Participación y Argumentación en Democracia', 'S', 'A'],
        ['SFI', 'Seminario de Filosofía', 'S', 'A'],
        ['FPO', 'Filosofía Política', 'S', 'A'],
        ['CHP', 'Comprensión Histórica del Presente', 'S', 'A'],
        ['GTE', 'Geografía, Territorio y Desafíos Socioambientales', 'S', 'A'],
        ['LDI', 'Límites, Derivadas e Integrales', 'S', 'B'],
        ['PES', 'Probabilidades y Estadística Descriptiva e Inferencial', 'S', 'B'],
        ['GEO', 'Geometría 3D', 'S', 'B'],
        ['BIO', 'Biología Celular y Molecular', 'S', 'B'],
        ['CSA', 'Ciencias de la Salud', 'S', 'B'],
        ['FIS', 'Física', 'S', 'B'],
        ['QUI', 'Química', 'S', 'B'],
        ['DAR', 'Diseño y Arquitectura', 'S', 'C'],
        ['IMU', 'Interpretación Musical', 'S', 'C'],
        ['CCM', 'Creación y Composición Musical', 'S', 'C'],
        ['PEV', 'Promoción de Estilos de Vida Activos y Saludables', 'S', 'C'],
        ['CEF', 'Ciencias del Ejercicio Físico y Salud', 'S', 'C'],
        ['AAV', 'Artes Visuales, Audiovisuales y Multimediales', 'S', 'C'],
    ];

    public function handle(): int
    {
        $grado = min(4, max(1, (int) $this->argument('grado')));
        $letra = strtoupper((string) $this->argument('letra'));
        $ano = (int) $this->argument('ano');
        $ensenanza = (string) $this->option('ensenanza');
        $n = max(4, (int) $this->option('alumnos'));
        $seed = (int) $this->option('seed');
        $suf = '0'.$grado;

        mt_srand($seed);
        $faker = FakerFactory::create('es_ES');
        $faker->seed($seed);

        $nomina = [];
        $calificaciones = [];

        for ($i = 0; $i < $n; $i++) {
            // Multiplicamos el seed por 1000 para separar los bloques de RUT por curso
            $runBase = 20000000 + ($seed * 1000) + $i;
            $run = $runBase.'-'.self::digitoVerificador($runBase);
            $nombres = self::azar(self::NOMBRES).' '.self::azar(self::SEGUNDOS_NOMBRES);
            $apellidos = self::azar(self::APELLIDOS).' '.self::azar(self::APELLIDOS);
            $telefono = '+569'.str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT);

            // Casos borde forzados (CA-3): índices 0..3.
            $esRetirado = $i === 0;
            // Rasgo 4° medio: año recortado por PAES, asistencia algo menor.
            $pisoAsistencia = $grado === 4 ? 70 : 78;
            $asistencia = $esRetirado
                ? '61,0'
                : self::decimalComa($faker->randomFloat(1, $pisoAsistencia, 100));

            $nomina[] = [
                $ano, $ensenanza, $grado, $letra, $run, $nombres, $apellidos,
                '04-03-'.$ano, $esRetirado ? '30-06-'.$ano : '', $asistencia, $telefono,
            ];

        foreach ($this->subsectoresAlumno($grado, $suf, $i) as [$cod, $nombre, $incide]) {
                foreach ([1, 2] as $semestre) {
                    
                    // Lógica temporal realista (Octubre):
                    // Semestre 1 cerrado: entre 5 y 6 notas.
                    // Semestre 2 en curso: entre 2 y 3 notas.
                    $cantidadNotas = ($semestre === 1) ? rand(5, 6) : rand(2, 3);
                    
                    for ($numNota = 1; $numNota <= $cantidadNotas; $numNota++) {
                        [$nota, $conceptual, $eximido] = $this->notaPara($cod, $suf, $i, $semestre, $faker);
                        
                        // Si el alumno está eximido o el ramo es conceptual (ej. Religión), 
                        // agregamos el registro solo 1 vez para no saturar la tabla con "EX"
                        if ($eximido === 'EX' || $conceptual !== '') {
                            if ($numNota === 1) {
                                $calificaciones[] = [
                                    $ano, $ensenanza, $grado, $letra, $run,
                                    $cod, $nombre, $incide, $nota, $conceptual, $eximido, $semestre,
                                ];
                            }
                            continue; 
                        }

                        // Agregamos la nota parcial normal
                        $calificaciones[] = [
                            $ano, $ensenanza, $grado, $letra, $run,
                            $cod, $nombre, $incide, $nota, $conceptual, $eximido, $semestre,
                        ];
                    }
                }
            }
        }

        $sufijoNivel = $ensenanza === '310' ? 'M' : '';
        $nombreArchivo = "nomina_calificaciones_{$ano}_{$grado}{$sufijoNivel}{$letra}.xlsx";
        if (!file_exists(storage_path('app/sinteticos'))) {
            mkdir(storage_path('app/sinteticos'), 0777, true);
            }
            // Forzamos la ruta exacta
        $ruta = storage_path('app/sinteticos/' . $nombreArchivo);

        @mkdir(dirname($ruta), 0777, true);

        $libro = new Spreadsheet;
        // Fecha fija derivada del seed: el writer no incorpora el reloj (CA-5).
        $fechaFija = 1740960000 + $seed;
        $libro->getProperties()->setCreated($fechaFija)->setModified($fechaFija);
        $hojaNomina = $libro->getActiveSheet();
        $hojaNomina->setTitle('NOMINA');
        $hojaNomina->fromArray([[
            'ANO', 'COD_ENSENANZA', 'GRADO', 'CURSO', 'RUN_TOKEN', 'NOMBRES',
            'APELLIDOS', 'FECHA_INCORPORACION', 'FECHA_RETIRO',
            'ASISTENCIA_PCT', 'TELEFONO_APODERADO',
        ]], null, 'A1');
        $hojaNomina->fromArray($nomina, null, 'A2');
        // El '+' inicial del teléfono se interpretaría como fórmula: forzar texto.
        foreach ($nomina as $idx => $filaNomina) {
            $hojaNomina->setCellValueExplicit(
                'K'.($idx + 2),
                $filaNomina[10],
                DataType::TYPE_STRING
            );
        }

        $hojaNotas = $libro->createSheet();
        $hojaNotas->setTitle('CALIFICACIONES');
        $hojaNotas->fromArray([[
            'ANO', 'COD_ENSENANZA', 'GRADO', 'CURSO', 'RUN_TOKEN',
            'COD_SUBSECTOR', 'SUBSECTOR', 'INCIDE_PROMOCION',
            'NOTA_FINAL', 'NOTA_CONCEPTUAL', 'EXIMIDO', 'SEMESTRE',
        ]], null, 'A1');
        $hojaNotas->fromArray($calificaciones, null, 'A2');

        (new Xlsx($libro))->save($ruta);

        // Huella del contenido (no del ZIP, cuyos mtimes dependen del reloj).
        $huella = hash('sha256', serialize([$nomina, $calificaciones]));

        $this->info("Archivo generado: {$ruta}");
        $this->info('Alumnos: '.$n.' | Filas NOMINA: '.count($nomina).' | Filas CALIFICACIONES: '.count($calificaciones));
        $this->info('SHA-256 contenido: '.$huella);
        $this->info("Bordes incluidos: retirado(1), cuello de botella MAT-{$suf}(1), conceptual REL-{$suf}(1), EX EFI-{$suf}(1)");

        return self::SUCCESS;
    }

    /**
     * Subsectores del alumno según grado.
     * 1°-2°: 7 fijos. 3°-4°: 6 común + 1 electivo + 3 diferenciados sorteados.
     * Los alumnos borde 2 y 3 reciben electivo forzado (REL / EFI).
     */
    private function subsectoresAlumno(int $grado, string $suf, int $indiceAlumno): array
    {
        if ($grado <= 2) {
            return array_map(
                fn ($s) => [$s[0].'-'.$suf, $s[1], $s[2]],
                self::PLAN_COMUN_12
            );
        }

        $conSuf = fn ($s) => [$s[0].'-'.$suf, $s[1], $s[2]];
        $lista = array_map($conSuf, self::PLAN_COMUN_34);

        $electivo = match ($indiceAlumno) {
            2 => self::ELECTIVOS_34[3], // borde conceptual → Religión
            3 => self::ELECTIVOS_34[2], // borde eximido → Ed. Física
            default => self::ELECTIVOS_34[mt_rand(0, 3)],
        };
        $lista[] = $conSuf($electivo);

        foreach (self::sorteo(self::DIFERENCIADOS, 3) as $dif) {
            $lista[] = [$dif[0].'-'.$suf, $dif[1], $dif[2]];
        }

        return $lista;
    }

    /**
     * Define nota/conceptual/eximido por subsector.
     * Retorna [NOTA_FINAL, NOTA_CONCEPTUAL, EXIMIDO] (excluyentes entre sí).
     */
    private function notaPara(string $cod, string $suf, int $indiceAlumno, int $semestre, $faker): array
    {
        // Borde: cuello de botella — alumno 1, varias notas de Matemática bajo 4.0.
        if ($cod === 'MAT-'.$suf && $indiceAlumno === 1) {
            return [$semestre === 1 ? '3,9' : '3,5', '', ''];
        }
        // Borde: nota conceptual — alumno 2, Religión.
        if ($cod === 'REL-'.$suf && $indiceAlumno === 2 && $semestre === 1) {
            return ['', 'S', ''];
        }
        // Borde: eximido — alumno 3, Educación Física semestre 1.
        if ($cod === 'EFI-'.$suf && $indiceAlumno === 3 && $semestre === 1) {
            return ['', '', 'EX'];
        }
        // Religión siempre conceptual fuera del borde.
        if (str_starts_with($cod, 'REL-')) {
            return ['', $faker->randomElement(['S', 'S', 'MB', 'B']), ''];
        }
        // Notas normales 4,0–7,0 con 15% de riesgo 3,0–3,9.
        $nota = $faker->boolean(15)
            ? $faker->randomFloat(1, 3.0, 3.9)
            : $faker->randomFloat(1, 4.0, 7.0);

        return [self::decimalComa($nota), '', ''];
    }

    private static function decimalComa(float $valor): string
    {
        return number_format(round($valor, 1), 1, ',', '');
    }

    /** Pick determinista con mt_rand (array_rand no respeta mt_srand). */
    private static function azar(array $lista): string
    {
        return $lista[mt_rand(0, count($lista) - 1)];
    }

    /** Sorteo determinista sin reposición (Fisher-Yates con mt_rand). */
    private static function sorteo(array $lista, int $cantidad): array
    {
        $indices = range(0, count($lista) - 1);
        for ($k = count($indices) - 1; $k > 0; $k--) {
            $j = mt_rand(0, $k);
            [$indices[$k], $indices[$j]] = [$indices[$j], $indices[$k]];
        }

        return array_map(fn ($pos) => $lista[$pos], array_slice($indices, 0, $cantidad));
    }

    /** Dígito verificador módulo 11 (RUN chileno). */
    public static function digitoVerificador(int $run): string
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
