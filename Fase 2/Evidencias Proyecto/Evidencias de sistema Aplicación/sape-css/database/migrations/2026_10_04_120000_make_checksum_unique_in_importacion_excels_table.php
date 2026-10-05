<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CA-2 (INDG-15/INDG-16): la re-carga idéntica no duplica.
     * El checksum del archivo identifica una importación ya procesada.
     */
    public function up(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->unique('checksum', 'importacion_excels_checksum_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->dropUnique('importacion_excels_checksum_unique');
        });
    }
};
