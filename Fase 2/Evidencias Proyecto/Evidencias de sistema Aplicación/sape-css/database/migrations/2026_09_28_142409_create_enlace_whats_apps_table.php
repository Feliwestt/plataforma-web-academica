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
        Schema::create('enlace_whats_apps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Profesor que envía
            $table->foreignUuid('matricula_id')->constrained('matriculas')->onDelete('cascade'); // Estudiante/Apoderado destino
            
            $table->string('url', 500);
            $table->text('mensajePrellenado');
            $table->string('telefonoDestinoSnapshot');
            $table->string('estado')->default('GENERADO'); // GENERADO, ABIERTO_POR_DOCENTE
            $table->timestamp('generadoEn')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enlace_whats_apps');
    }
};
