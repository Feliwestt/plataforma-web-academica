<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () { 
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/importar-notas', [ImportController::class, 'importar'])->name('importar.notas');


    // 1. Mostrar la pantalla del formulario (petición GET)
    Route::get('/admin/importar', [ImportController::class, 'index'])->name('importar.vista');

    // 2. Procesar el archivo enviado desde el formulario (petición POST)
    Route::post('/admin/importar', [ImportController::class, 'importar'])->name('importar.notas');
    // Opción B: Si prefieres manejar la lógica desde un controlador (descomenta esta opción y comenta la A)
    // Route::get('/panel-directivo', [DashboardController::class, 'directivo'])->name('panel.directivo');
    // Route::get('/panel-docente', [DashboardController::class, 'docente'])->name('panel.docente');
});

require __DIR__.'/auth.php';