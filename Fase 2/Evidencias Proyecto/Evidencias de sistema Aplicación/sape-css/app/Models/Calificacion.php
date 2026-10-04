<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    // 1. Le decimos el nombre exacto de la tabla en español
    protected $table = 'calificaciones';

    // 2. Por si acaso
    protected $keyType = 'string';
    public $incrementing = false;

    // ... aquí sigue tu función asignatura() que ya tenías
    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }
}