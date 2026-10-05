<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'asignatura_curso')
            ->withPivot('profesor_id');
    }
}
