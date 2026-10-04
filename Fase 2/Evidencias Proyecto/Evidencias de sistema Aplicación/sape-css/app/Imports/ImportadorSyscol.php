<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Import;

class ImportadorSyscol implements WithMultipleSheets, Import 
{
    use Importable;

    public function sheets(): array
    {
        return [
            // 1. Primero lee NOMINA para crear a los estudiantes
            'NOMINA'         => new NominaSheetImport(),
            
            // 2. Luego lee CALIFICACIONES para asignarles las notas
            'CALIFICACIONES' => new CalificacionesSheetImport(),
        ];
    }
}