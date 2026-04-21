<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->decimal('anio', 10, 0);
            $table->string('plan', 6);
            $table->string('codigo', 7);
            $table->string('sigla', 45)->nullable();
            $table->string('nombre', 192)->nullable();
            $table->string('nivel', 1)->nullable();
            $table->string('periodo', 4)->nullable();
            $table->string('obligatorio', 1)->nullable();
            $table->string('tipo', 40);
            $table->string('activo', 1)->nullable();
            $table->decimal('departamento', 10, 0)->nullable();
            // Sin primary key — tiene duplicados históricos intencionales
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};