<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La importación masiva y la matriz de cursos necesitan saber a qué
     * curso/año pertenece cada archivo procesado.
     */
    public function up(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->foreignUuid('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->integer('anio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->dropForeign(['curso_id']);
            $table->dropColumn(['curso_id', 'anio']);
        });
    }
};
