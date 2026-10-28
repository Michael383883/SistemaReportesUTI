<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_add_bloqueo_a_periodos_academicos_table.php
    public function up(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            if (!Schema::hasColumn('periodos_academicos', 'bloqueado')) {
                $table->boolean('bloqueado')->default(false);
            }
            if (!Schema::hasColumn('periodos_academicos', 'bloqueado_anio')) {
                $table->integer('bloqueado_anio')->nullable();
            }
            if (!Schema::hasColumn('periodos_academicos', 'bloqueado_por')) {
                $table->foreignId('bloqueado_por')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            }
            if (!Schema::hasColumn('periodos_academicos', 'bloqueado_en')) {
                // Precisión (3) => Laravel genera 'datetime2(3)' en sqlsrv en
                // vez de 'datetime', evitando el problema de conversión.
                $table->timestamp('bloqueado_en', 3)->nullable();
            }
        });

        // Fix: en SQL Server, 'datetime' interpreta de forma ambigua los
        // strings ISO que genera Laravel al hacer save() (según el
        // DATEFORMAT/LANGUAGE de la sesión), lo que dispara el error
        // SQLSTATE[22007] al actualizar updated_at. 'datetime2' no tiene
        // esa ambigüedad. created_at/updated_at ya existían como 'datetime'
        // desde la migración original de la tabla, así que acá los migramos.
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->timestamp('created_at', 3)->nullable()->change();
            $table->timestamp('updated_at', 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });

        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->dropForeign(['bloqueado_por']);
            $table->dropColumn(['bloqueado', 'bloqueado_anio', 'bloqueado_por', 'bloqueado_en']);
        });
    }
};