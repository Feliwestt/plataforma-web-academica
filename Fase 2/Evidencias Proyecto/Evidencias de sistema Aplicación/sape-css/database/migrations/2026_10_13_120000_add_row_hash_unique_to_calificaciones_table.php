<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anti-duplicados lógicos (reimportación "casi igual"): la huella de la
     * fila identifica una calificación ya importada aunque el archivo tenga
     * distinto hash. La fecha queda fuera de la huella a propósito: el Excel
     * no trae fecha por nota y el importador usa una fija determinista por
     * semestre. Nullable para no romper las filas históricas (NULL no
     * colisiona en unique de Postgres).
     */
    public function up(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->string('row_hash', 64)->nullable()->after('fecha');
            $table->unique('row_hash', 'calificaciones_row_hash_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropUnique('calificaciones_row_hash_unique');
            $table->dropColumn('row_hash');
        });
    }
};
