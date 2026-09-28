<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Import; // <-- Importante

class ImportadorSyscol implements WithMultipleSheets, Import 
{
    use Importable;

    public function sheets(): array
    {
        return [
            'NOMINA'         => new NominaSheetImport(),
            'CALIFICACIONES' => new CalificacionesSheetImport(),
        ];
    }
}