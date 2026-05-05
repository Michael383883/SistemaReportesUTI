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
        Schema::create('resoluciones_pdf', function (Blueprint $table) {
            $table->increments('id_resolucion');
            $table->string('nro_resolucion', 50);
            $table->string('descripcion', 200)->nullable();
            $table->integer('anio');
            $table->string('periodo', 2);

            $table->binary('archivo_pdf'); // PostgreSQL => BYTEA

            $table->string('nombre_archivo', 200);
            $table->integer('tamanio_kb')->nullable();
            $table->timestamp('fecha_subida')->useCurrent();
            $table->string('subido_por', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resoluciones_pdf');
    }
};
