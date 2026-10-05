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
        Schema::create('fila_importacions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('importacion_id')->constrained('importacion_excels')->onDelete('cascade');
            $table->integer('numeroFila');
            $table->string('hoja')->nullable();
            $table->string('estado');
            $table->text('motivoRechazo')->nullable();
            $table->json('datosNormalizados')->nullable(); // Guardamos la fila cruda como JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fila_importacions');
    }
};
