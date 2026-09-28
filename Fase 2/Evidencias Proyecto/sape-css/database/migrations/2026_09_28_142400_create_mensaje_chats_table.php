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
        Schema::create('mensaje_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sesion_id')->constrained('sesion_chats')->onDelete('cascade');
            $table->string('autor'); // DOCENTE, ASISTENTE_IA
            $table->text('contenido');
            $table->timestamp('creadoEn')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensaje_chats');
    }
};
