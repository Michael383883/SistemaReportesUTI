<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1) CLASIFICACION_DOCUMENTO
        // ─────────────────────────────────────────────
        Schema::create('CLASIFICACION_DOCUMENTO', function (Blueprint $table) {

            $table->increments('ID_DOCUMENTO');

            $table->text('TIPO_DOCUMENTO')->nullable();
            $table->text('DETALLE_GENERAL')->nullable();
            $table->string('CATEGORIA', 50)->nullable();
            $table->string('NIVEL', 50)->nullable();
            $table->string('GESTION', 10)->nullable();
            $table->string('PERIODO', 30)->nullable();
            $table->boolean('FOTOCOPIA_TITULAR')->default(false);
            $table->string('RUTA_ARCHIVO', 255)->nullable();
            $table->string('NOMBRE_ARCHIVO', 255)->nullable();
            $table->string('OBSERVACION', 300)->nullable();
            $table->string('OBSERVACION2', 300)->nullable();
            $table->timestamp('FECHA_REGISTRO')->useCurrent();

            $table->index(['CATEGORIA', 'NIVEL', 'GESTION']);
        });

        // ─────────────────────────────────────────────
        // 2) CLASIFICACION_DOCENTE
        //    Uno o varios docentes por documento.
        // ─────────────────────────────────────────────
        Schema::create('CLASIFICACION_DOCENTE', function (Blueprint $table) {

            $table->increments('ID_CLASIFICACION_DOCENTE');

            $table->unsignedInteger('ID_DOCUMENTO');

            $table->foreign('ID_DOCUMENTO')
                ->references('ID_DOCUMENTO')
                ->on('CLASIFICACION_DOCUMENTO')
                ->onDelete('cascade');
        });

        DB::statement("
            ALTER TABLE CLASIFICACION_DOCENTE
            ADD COD_DOCENTE NUMERIC(9,0) NOT NULL
        ");

        DB::statement("
            ALTER TABLE CLASIFICACION_DOCENTE
            ADD CONSTRAINT FK_CLASIF_DOCENTE_DOCENTE
            FOREIGN KEY (COD_DOCENTE)
            REFERENCES DOCENTES(CODIGO)
        ");

        // Un docente no puede tener dos filas dentro del mismo documento,
        // pero sí puede tener varias materias asignadas (vía CLASIFICACION_MATERIA)
        // y puede aparecer en cuantos otros documentos se quiera.
        DB::statement("
            ALTER TABLE CLASIFICACION_DOCENTE
            ADD CONSTRAINT UQ_CLASIF_DOCENTE_DOCUMENTO UNIQUE (ID_DOCUMENTO, COD_DOCENTE)
        ");

        // ─────────────────────────────────────────────
        // 3) CLASIFICACION_MATERIA
        // ─────────────────────────────────────────────
        Schema::create('CLASIFICACION_MATERIA', function (Blueprint $table) {

            $table->increments('ID_DETALLE');

            $table->unsignedInteger('ID_DOCUMENTO');
            $table->unsignedInteger('ID_CLASIFICACION_DOCENTE')->nullable();

            $table->string('COD_MATERIA', 10)->nullable();
            $table->string('NOMBRE_MATERIA', 150);
            $table->string('COD_PLAN', 10)->nullable();
            $table->integer('NOTA')->nullable();
            $table->text('DETALLE')->nullable();
            $table->integer('ORDEN')->nullable();

            $table->foreign('ID_DOCUMENTO')
                ->references('ID_DOCUMENTO')
                ->on('CLASIFICACION_DOCUMENTO')
                ->onDelete('cascade');

            // SQL Server no permite múltiples rutas de cascada; esta FK
            // se deja sin acción automática. Al borrar el documento completo,
            // la fila de MATERIA ya se elimina por la FK de arriba de todas
            // formas, así que no queda huérfana.
            $table->foreign('ID_CLASIFICACION_DOCENTE')
                ->references('ID_CLASIFICACION_DOCENTE')
                ->on('CLASIFICACION_DOCENTE')
                ->onDelete('no action')
                ->onUpdate('no action');
        });

        // ─────────────────────────────────────────────
        // 4) CLASIFICACION_REFERENCIA
        // ─────────────────────────────────────────────
        Schema::create('CLASIFICACION_REFERENCIA', function (Blueprint $table) {

            $table->increments('ID_REF');

            $table->unsignedInteger('ID_DOCUMENTO');
            $table->string('NRO_REFERENCIA', 50);
            $table->integer('ID_RESOLUCION')->nullable();

            $table->foreign('ID_DOCUMENTO')
                ->references('ID_DOCUMENTO')
                ->on('CLASIFICACION_DOCUMENTO')
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
        Schema::dropIfExists('CLASIFICACION_DOCUMENTO');
    }
};