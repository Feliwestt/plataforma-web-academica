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
        Schema::create('sesion_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // El docente que usa el chat
            $table->timestamp('iniciadaEn')->useCurrent();
            $table->timestamp('expiraEn')->nullable();
            $table->string('estado')->default('ACTIVA'); // ACTIVA, EXPIRADA, FINALIZADA
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesion_chats');
    }
};
