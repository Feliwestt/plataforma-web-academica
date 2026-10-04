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
        Schema::create('registro_bitacoras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Quién hizo la acción
            
            $table->string('accion');
            $table->string('recursoTipo'); // Ej: 'InformeDerivacion', 'EnlaceWhatsApp'
            $table->uuid('recursoId')->nullable();
            $table->json('metadatos')->nullable();
            $table->string('hashIntegridad')->nullable();
            $table->timestamp('creadoEn')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_bitacoras');
    }
};
