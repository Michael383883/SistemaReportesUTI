<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('API_CLIENTES', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('NOMBRE', 100);
            $table->char('TOKEN_HASH', 64)->unique();
            $table->boolean('ACTIVO')->default(true);
            $table->dateTime('CREADO_EN')->useCurrent();
            $table->dateTime('ULTIMO_USO')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('API_CLIENTES');
    }
};