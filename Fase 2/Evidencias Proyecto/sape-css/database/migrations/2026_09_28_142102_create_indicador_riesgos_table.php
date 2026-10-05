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
        Schema::create('indicador_riesgos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('matricula_id')->constrained('matriculas')->onDelete('cascade');
            $table->foreignUuid('criterio_id')->constrained('criterio_riesgos')->onDelete('cascade');

            $table->string('nivel'); // BAJO, MEDIO, ALTO
            $table->decimal('puntaje', 5, 2)->nullable();
            $table->text('explicacion')->nullable();
            $table->timestamp('calculadoEn')->useCurrent();
            $table->string('estadoRevision')->default('PENDIENTE'); // PENDIENTE, REVISADO, DESCARTADO
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicador_riesgos');
    }
};
