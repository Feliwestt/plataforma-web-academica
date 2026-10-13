<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cola de importación: archivo pendiente en disco, total de filas para
     * la barra de proceso y mensaje de error terminal.
     */
    public function up(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->string('ruta_archivo')->nullable()->after('checksum');
            $table->integer('filas_totales')->nullable()->after('filasRechazadas');
            $table->text('error')->nullable()->after('filas_totales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->dropColumn(['ruta_archivo', 'filas_totales', 'error']);
        });
    }
};
