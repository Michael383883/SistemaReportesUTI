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
        Schema::create('horarios2', function (Blueprint $table) {

            $table->id();

            // AÑO Y PERIODOS
            $table->integer('anio')->nullable();
            $table->integer('periodo')->nullable();

            // DÍA
            $table->string('dia', 2)->nullable();

            // HORARIOS
            $table->integer('periodo_horario')->nullable();
            $table->integer('hora')->nullable();

            // UBICACIÓN
            $table->integer('edificio')->nullable();
            $table->integer('piso');

            // DATOS ACADÉMICOS
            $table->string('ambiente', 7)->nullable();
            $table->string('materia', 7)->nullable();
            $table->string('grupo', 2)->nullable();

            // DOCENTE
            // CAMBIADO DE decimal() A bigint()
            // porque existen códigos grandes como:
            // 198500027, 198700032, etc.
            $table->bigInteger('docente')->nullable();

            // TIPO
            $table->string('tipo', 1)->nullable();

            // RELACIÓN HORARIA
            $table->integer('periodo_hora')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios2');
    }
};