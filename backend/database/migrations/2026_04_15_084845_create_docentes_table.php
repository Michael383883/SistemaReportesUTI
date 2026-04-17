<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {

            $table->decimal('codigo', 10, 0)->primary();

            $table->string('ci', 13)->nullable(); // 
            $table->string('nombres', 40)->nullable(); // 
            $table->string('apellidos', 40)->nullable();
            $table->dateTime('fecha_nac')->nullable();
            $table->string('sexo', 1)->nullable(); // 
            $table->string('titulo', 4)->nullable();
            $table->dateTime('fecha_nombramiento')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};