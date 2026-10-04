<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    // Si el ID de estudiante es UUID, recuerda poner estas dos líneas:
    protected $keyType = 'string';
    public $incrementing = false;

    public function calificaciones()
    {
    // Un estudiante tiene calificaciones a través de su matrícula
        return $this->hasManyThrough(
            Calificacion::class,
            Matricula::class,
            'estudiante_id', // Llave foránea en la tabla matriculas
            'matricula_id',  // Llave foránea en la tabla calificaciones
            'id',            // Llave local en estudiantes
            'id'             // Llave local en matriculas
        );
    }
}