<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuartil_detalle_curriculars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curso_id')->constrained('cursos')->onDelete('cascade'); // Asociado al curso
            
            $table->decimal('promedioCurso', 3, 1);
            $table->decimal('desviacionEstandar', 5, 2)->nullable();
            $table->text('explicacion')->nullable();
            $table->timestamp('detectadoEn')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuartil_detalle_curriculars');
    }
};
