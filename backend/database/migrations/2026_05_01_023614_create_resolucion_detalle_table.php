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
        Schema::create('resolucion_detalle', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_resolucion');
            $table->decimal('cod_docente', 10, 0);
            $table->string('cod_plan', 10);
            $table->string('cod_materia', 10);
            $table->string('grupo', 5)->nullable();
            $table->string('tipo', 2)->nullable();
            $table->string('observacion', 200)->nullable();

            $table->foreign('id_resolucion')
                ->references('id_resolucion')
                ->on('resoluciones_pdf')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resolucion_detalle');
    }
};
