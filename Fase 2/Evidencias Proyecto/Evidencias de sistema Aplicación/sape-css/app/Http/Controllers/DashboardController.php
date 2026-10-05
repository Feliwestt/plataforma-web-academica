<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
        {
            $user = $request->user();

            // Si es el Director, mostramos el Dashboard de la carpeta Director
            if ($user->hasRole('Administrador')) {
                return Inertia::render('Director/Dashboard');
            }

            // Si es Profesor, mostramos la vista hermosa con las tarjetas de Jefatura y Asignatura
            if ($user->hasRole('Profesor Jefe') || $user->hasRole('Profesor de Asignatura')) {
                return Inertia::render('Profesor/Dashboard');
            }

            // Respaldo en caso de que un usuario no tenga rol
            return Inertia::render('Dashboard');
        }
        
    public function panelDirector()
    {
        // Más adelante aquí enviaremos métricas de todo el colegio.
        // Por ahora, solo cargaremos la vista.
        return Inertia::render('Director/PanelDirector');
    }
}