<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de configuración para los 4 periodos académicos.
 *
 * OJO: esta tabla vive en la conexión POR DEFECTO de Laravel (la misma
 * donde están tus tablas de `users`, etc.), NO en la conexión `sqlsrv`
 * donde están GRUPOS / DOCENTES / MATERIAS.
 *
 * Los datos iniciales se insertan vía seeder (PeriodosAcademicosSeeder),
 * no en esta migración. Ver ese archivo para más contexto sobre el bug
 * de conversión de tipos con ODBC Driver 17 que motivó separar esto.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();
            $table->string('periodo', 1)->unique(); // '1' Sem I, '2' Sem II, '3' Verano, '4' Invierno
            $table->string('nombre', 40);
            $table->string('inicio', 5); // formato MM-DD, ej: '01-05'
            $table->string('fin', 5);    // formato MM-DD, ej: '02-20'
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_academicos');
    }
};
