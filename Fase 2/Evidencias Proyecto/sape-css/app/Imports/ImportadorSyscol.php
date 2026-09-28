<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportadorSyscol implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'NOMINA'         => new NominaSheetImport(),
            'CALIFICACIONES' => new CalificacionesSheetImport(),
        ];
    }
}