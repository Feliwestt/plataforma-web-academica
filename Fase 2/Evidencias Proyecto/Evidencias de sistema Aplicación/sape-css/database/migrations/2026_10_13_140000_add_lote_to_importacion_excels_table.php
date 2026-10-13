<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lote de subida: los archivos encolados en un mismo POST comparten
     * lote_id para agrupar el historial por fecha/hora de subida.
     */
    public function up(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->uuid('lote_id')->nullable()->after('id');
            $table->index('lote_id', 'importacion_excels_lote_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacion_excels', function (Blueprint $table) {
            $table->dropIndex('importacion_excels_lote_id_index');
            $table->dropColumn('lote_id');
        });
    }
};
