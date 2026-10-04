<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Asignar Profesor Jefe al curso
        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('profesor_jefe_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
        });

        // 2. Asignar Profesor de Asignatura a la tabla pivote
        // Nota: Si tu tabla se llama distinto (ej. 'asignatura_cursos'), cámbialo aquí
        Schema::table('asignatura_curso', function (Blueprint $table) {
            $table->foreignId('profesor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropForeign(['profesor_jefe_id']);
            $table->dropColumn('profesor_jefe_id');
        });

        Schema::table('asignatura_curso', function (Blueprint $table) {
            $table->dropForeign(['profesor_id']);
            $table->dropColumn('profesor_id');
        });
    }
};