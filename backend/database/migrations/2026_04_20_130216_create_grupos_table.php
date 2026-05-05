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
            $table->integer('anio');
            $table->string('periodo', 4);
            $table->string('plan', 6)->nullable();
            $table->string('materia', 7);
            $table->string('grupo', 2);
            $table->integer('docente');
            $table->string('primario', 1)->nullable();
            $table->integer('cuota')->nullable();
            $table->string('tipo', 1)->nullable();
            $table->integer('quota_plan')->nullable();
            $table->string('resolucion', 100)->nullable();
            $table->string('designacion', 1000)->nullable();
            $table->string('tiempo', 50)->nullable();

            
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
