<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfesorController extends Controller
{
    public function panelJefe(Request $request)
    {
        $curso = Curso::with([
            'estudiantes',
            'estudiantes.calificaciones' => function ($query) {
                // AQUÍ ESTÁ LA MAGIA: Le decimos explícitamente que traiga TODAS las columnas de calificaciones
                $query->select('calificaciones.*', 'asignaturas.nombre as asignatura_nombre', 'asignatura_curso.asignatura_id')
                    ->join('asignatura_curso', 'calificaciones.asignatura_curso_id', '=', 'asignatura_curso.id')
                    ->join('asignaturas', 'asignatura_curso.asignatura_id', '=', 'asignaturas.id');
            },
        ])
            ->where('profesor_jefe_id', $request->user()->id)
            ->first();

        return Inertia::render('Profesor/PanelJefe', [
            'curso' => $curso,
        ]);
    }

    public function panelAsignatura(Request $request)
    {
        $profesorId = $request->user()->id;

        $cursos = Curso::whereHas('asignaturas', function ($query) use ($profesorId) {
            $query->where('asignatura_curso.profesor_id', $profesorId);
        })
            ->with([
                'estudiantes', // <-- ¡ESTO FALTABA! Carga los datos base del alumno
                'asignaturas' => function ($q) use ($profesorId) {
                    $q->where('asignatura_curso.profesor_id', $profesorId);
                },
                'estudiantes.calificaciones' => function ($query) use ($profesorId) {
                    // Replicamos la misma magia que usamos en el Profesor Jefe
                    $query->select('calificaciones.*', 'asignaturas.nombre as asignatura_nombre', 'asignatura_curso.asignatura_id')
                        ->join('asignatura_curso', 'calificaciones.asignatura_curso_id', '=', 'asignatura_curso.id')
                        ->join('asignaturas', 'asignatura_curso.asignatura_id', '=', 'asignaturas.id')
                        ->where('asignatura_curso.profesor_id', $profesorId);
                },
            ])
            ->get();

        return Inertia::render('Profesor/PanelAsignatura', [
            'cursos' => $cursos,
        ]);
    }
}
