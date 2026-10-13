<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La cola se eliminó (importación sincrónica): ningún archivo pendiente
     * vuelve a guardarse en disco, la columna quedó sin uso.
     */
    public function up(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->dropColumn('ruta_archivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->string('ruta_archivo')->nullable()->after('checksum');
        });
    }
};
