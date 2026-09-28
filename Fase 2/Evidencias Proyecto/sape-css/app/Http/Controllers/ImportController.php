<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportadorSyscol;
use Illuminate\Support\Facades\DB;
use Exception;
use Inertia\Inertia;

class ImportController extends Controller
{

    // AGREGA ESTE MÉTODO:
    public function index()
    {
        // Retorna la vista Inertia de la página de importación
        return Inertia::render('Admin/ImportarNotas'); // Asegúrate de que este archivo exista en tu frontend
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            DB::beginTransaction();

            // Puedes usar cualquiera de estas dos formas:
            Excel::import(new ImportadorSyscol, $request->file('archivo_excel'));

            DB::commit();
            return back()->with('success', 'El archivo ha sido procesado e importado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['archivo_excel' => 'Error crítico al procesar: ' . $e->getMessage()]);
        }
    }
}
