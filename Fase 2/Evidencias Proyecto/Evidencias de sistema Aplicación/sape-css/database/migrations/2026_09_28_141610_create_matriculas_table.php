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
        Schema::create('matriculas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignUuid('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->foreignUuid('apoderado_id')->nullable()->constrained('apoderados')->nullOnDelete();

            $table->date('fechaIngreso');
            $table->string('estado');
            $table->boolean('vigente')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
