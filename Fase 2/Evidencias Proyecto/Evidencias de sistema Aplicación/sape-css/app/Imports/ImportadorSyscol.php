<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportadorSyscol implements Import, WithMultipleSheets
{
    use Importable;

    public NominaSheetImport $nomina;

    public CalificacionesSheetImport $calificaciones;

    public function __construct()
    {
        $this->nomina = new NominaSheetImport;
        $this->calificaciones = new CalificacionesSheetImport;
    }

    public function sheets(): array
    {
        return [
            // 1. Primero lee NOMINA para crear a los estudiantes
            'NOMINA' => $this->nomina,

            // 2. Luego lee CALIFICACIONES para asignarles las notas
            'CALIFICACIONES' => $this->calificaciones,
        ];
    }
}
