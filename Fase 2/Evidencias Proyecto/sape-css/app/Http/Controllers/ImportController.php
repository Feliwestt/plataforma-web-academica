<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportadorSyscol;
use Illuminate\Support\Facades\DB;
use Exception;


class ImportController extends Controller
{
    public function importar(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // Ejecutamos la lectura del archivo
            Excel::import(new ImportadorSyscol, $request->file('archivo_excel'));

            DB::commit();
            return back()->with('success', 'El archivo ha sido procesado e importado exitosamente.');

        } catch (Exception $e) {
            DB::rollBack();
            // Devuelve el error crítico a la vista de React
            return back()->withErrors(['archivo_excel' => 'Error crítico cancelando operación: ' . $e->getMessage()]);
        }
    }
}
