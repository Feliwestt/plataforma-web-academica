<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirección basada en los roles definidos en tu Seeder
        if ($user->hasRole('Administrador')) {
            return Inertia::render('Admin/Dashboard', [
                'userName' => $user->name
            ]);
        } 
        
        if ($user->hasRole('Director')) {
            return Inertia::render('Director/Dashboard', [
                'userName' => $user->name
            ]);
        } 
        
        if ($user->hasRole('Profesor Jefe')) {
            return Inertia::render('Profesor/Dashboard', [
                'userName' => $user->name
            ]);
        }

        // Fallback genérico en caso de que un usuario no tenga rol asignado
        return Inertia::render('Dashboard', [
            'userName' => $user->name
        ]);
    }
}