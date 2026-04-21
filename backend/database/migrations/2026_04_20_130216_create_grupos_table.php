<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->decimal('anio', 10, 0);
            $table->string('periodo', 4);
            $table->string('plan', 6)->nullable();
            $table->string('materia', 7);
            $table->string('grupo', 2);
            $table->decimal('docente', 10, 0);
            $table->string('primario', 1)->nullable();
            $table->decimal('cuota', 10, 0)->nullable();
            $table->string('tipo', 1)->nullable();
            $table->decimal('quota_plan', 10, 0)->nullable();
            $table->string('resolucion', 100)->nullable();
            $table->string('designacion', 1000)->nullable();
            $table->string('tiempo', 50)->nullable();

            //$table->primary(['anio', 'periodo', 'materia', 'grupo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
