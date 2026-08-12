<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('CLASIFICACION_TITULO', function (Blueprint $table) {
            $table->increments('ID_TITULO');

            $table->unsignedInteger('ID_DOCUMENTO');
            $table->unsignedInteger('ID_CLASIFICACION_DOCENTE')->nullable();

            $table->string('TIPO_TITULO', 50);       // Diplomado, Especialidad, Maestría, Doctorado, etc.
            $table->string('UNIVERSIDAD', 150)->nullable();
            $table->string('PAIS', 50)->nullable();
            $table->date('FECHA_TITULO')->nullable();
            $table->string('NOMBRE_TITULO', 200);
            $table->string('NUMERO', 30)->nullable();
            $table->timestamp('FECHA_REGISTRO')->useCurrent();

            $table->foreign('ID_DOCUMENTO')
                ->references('ID_DOCUMENTO')
                ->on('CLASIFICACION_DOCUMENTO')
                ->onDelete('cascade');

            // Igual que en CLASIFICACION_MATERIA: sin acción automática porque
            // SQL Server no permite múltiples rutas de cascada. Al borrar el
            // documento, esta fila ya se elimina por la FK de arriba.
            $table->foreign('ID_CLASIFICACION_DOCENTE')
                ->references('ID_CLASIFICACION_DOCENTE')
                ->on('CLASIFICACION_DOCENTE')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->index(['ID_DOCUMENTO', 'TIPO_TITULO']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CLASIFICACION_TITULO');
    }
};