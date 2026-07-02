<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('CLASIFICACION_DOCENTE', function (Blueprint $table) {

            $table->increments('ID_CLASIFICACION');

            $table->string('NIVEL', 20);
            $table->string('GESTION', 10)->nullable();
            $table->string('PERIODO', 30)->nullable();

            $table->text('DETALLE_GENERAL')->nullable();

            $table->boolean('FOTOCOPIA_TITULAR')->default(false);

            $table->string('RUTA_ARCHIVO', 255)->nullable();
            $table->string('NOMBRE_ARCHIVO', 255)->nullable();

            $table->string('OBSERVACION', 300)->nullable();
            $table->string('OBSERVACION2', 300)->nullable();

            $table->timestamp('FECHA_REGISTRO')->useCurrent();

            $table->index(['NIVEL', 'GESTION']);
        });

        // Crear exactamente el mismo tipo que DOCENTES.CODIGO
        DB::statement("
            ALTER TABLE CLASIFICACION_DOCENTE
            ADD COD_DOCENTE NUMERIC(9,0) NOT NULL
        ");

        // Crear la FK
        DB::statement("
            ALTER TABLE CLASIFICACION_DOCENTE
            ADD CONSTRAINT FK_CLASIFICACION_DOCENTE_DOCENTE
            FOREIGN KEY (COD_DOCENTE)
            REFERENCES DOCENTES(CODIGO)
        ");

        Schema::create('CLASIFICACION_MATERIA', function (Blueprint $table) {

            $table->increments('ID_DETALLE');

            $table->unsignedInteger('ID_CLASIFICACION');

            $table->string('COD_MATERIA', 10)->nullable();  // ← SOLO UNA VEZ
            $table->string('NOMBRE_MATERIA', 150);
            $table->string('COD_PLAN', 10)->nullable();
            // $table->string('COD_MATERIA', 10)->nullable();  // ← ELIMINADA (estaba duplicada)
            $table->integer('NOTA')->nullable();  // ← Esta columna existe en tu BD?

            $table->text('DETALLE')->nullable();

            $table->integer('ORDEN')->nullable();

            $table->foreign('ID_CLASIFICACION')
                ->references('ID_CLASIFICACION')
                ->on('CLASIFICACION_DOCENTE')
                ->onDelete('cascade');
        });

        Schema::create('CLASIFICACION_REFERENCIA', function (Blueprint $table) {

            $table->increments('ID_REF');

            $table->unsignedInteger('ID_CLASIFICACION');

            $table->string('NRO_REFERENCIA', 50);

            $table->integer('ID_RESOLUCION')->nullable();

            $table->foreign('ID_CLASIFICACION')
                ->references('ID_CLASIFICACION')
                ->on('CLASIFICACION_DOCENTE')
                ->onDelete('cascade');

            $table->foreign('ID_RESOLUCION')
                ->references('ID_RESOLUCION')
                ->on('RESOLUCIONES_PDF');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CLASIFICACION_REFERENCIA');
        Schema::dropIfExists('CLASIFICACION_MATERIA');
        Schema::dropIfExists('CLASIFICACION_DOCENTE');
    }
};