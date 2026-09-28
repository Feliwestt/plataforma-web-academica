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
        Schema::create('informe_derivacions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Docente que lo genera
            $table->foreignUuid('estudiante_id')->constrained('estudiantes')->onDelete('cascade'); // Alumno derivado
            
            $table->text('apuntesMinimos');
            $table->text('borrador')->nullable();
            $table->text('contenidoRevisado')->nullable();
            $table->string('estado')->default('BORRADOR'); // BORRADOR, REVISADO, EXPORTADO
            $table->timestamp('generadoEn')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informe_derivacions');
    }
};
