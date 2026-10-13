<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::post('/chat-pedagogico', [ChatbotController::class, 'sendMessage'])->name('chat.pedagogico')->middleware('auth');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Procesar los archivos enviados (POST) - Importación masiva ETL por cola
    Route::post('/admin/importar', [ImportController::class, 'importar'])->name('admin.importar.store');

    // Estado en vivo de las importaciones (polling de la vista)
    Route::get('/admin/importaciones/estado', [ImportController::class, 'estado'])->name('admin.importaciones.estado');

    // Eliminar un curso completo (solo desarrollo, solo Administrador)
    Route::delete('/admin/cursos', [ImportController::class, 'destroyCurso'])->name('admin.cursos.destroy');

    Route::get('/panel-jefe', [ProfesorController::class, 'panelJefe'])->name('profesor.jefe');
    Route::get('/panel-asignatura', [ProfesorController::class, 'panelAsignatura'])->name('profesor.asignatura');
    Route::get('/panel-director', [DashboardController::class, 'panelDirector'])->name('admin.director');

});

require __DIR__.'/auth.php';
