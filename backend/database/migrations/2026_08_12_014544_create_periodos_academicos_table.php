<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Tabla de configuración para los 4 periodos académicos.
 *
 * OJO: esta tabla vive en la conexión POR DEFECTO de Laravel (la misma
 * donde están tus tablas de `users`, etc.), NO en la conexión `sqlsrv`
 * donde están GRUPOS / DOCENTES / MATERIAS. Es una tabla de configuración
 * propia de la app, no parte del sistema académico legado.
 *
 * Si prefieres tenerla en sqlsrv, agrega ->connection('sqlsrv') aquí y
 * $connection = 'sqlsrv' en el modelo PeriodoAcademico.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();
            $table->char('periodo', 1)->unique(); // '1' Sem I, '2' Sem II, '3' Verano, '4' Invierno
            $table->string('nombre', 40);
            $table->char('inicio', 5); // formato MM-DD, ej: '01-05'
            $table->char('fin', 5);    // formato MM-DD, ej: '02-20'
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Valores por defecto = los mismos que estaban hardcodeados en
        // ReporteDocenteController::rangosPeriodos(). Así el comportamiento
        // no cambia hasta que un admin edite algo desde el nuevo módulo.
        DB::table('periodos_academicos')->insert([
            ['periodo' => '3', 'nombre' => 'Curso de Verano', 'inicio' => '01-05', 'fin' => '02-20', 'created_at' => now(), 'updated_at' => now()],
            ['periodo' => '1', 'nombre' => 'Semestre I', 'inicio' => '02-10', 'fin' => '06-30', 'created_at' => now(), 'updated_at' => now()],
            ['periodo' => '4', 'nombre' => 'Curso de Invierno', 'inicio' => '07-01', 'fin' => '08-15', 'created_at' => now(), 'updated_at' => now()],
            ['periodo' => '2', 'nombre' => 'Semestre II', 'inicio' => '08-05', 'fin' => '12-20', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_academicos');
    }
};
