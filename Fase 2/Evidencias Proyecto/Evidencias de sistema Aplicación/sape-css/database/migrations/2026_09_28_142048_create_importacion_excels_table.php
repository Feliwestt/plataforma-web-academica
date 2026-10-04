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
        Schema::create('importacion_excels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('archivoOriginal');
            $table->string('checksum')->nullable();
            $table->timestamp('iniciadaEn')->useCurrent();
            $table->timestamp('finalizadaEn')->nullable();
            $table->string('estadoImportacion')->default('PENDIENTE'); // VALIDADA, PROCESADA, etc.
            $table->integer('filasProcesadas')->default(0);
            $table->integer('filasRechazadas')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacion_excels');
    }
};
