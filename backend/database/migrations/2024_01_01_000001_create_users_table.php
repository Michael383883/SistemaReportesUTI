<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 30)->default('uti');
            $table->boolean('active')->default(1);
            $table->rememberToken();

            // datetime2 acepta milisegundos — soluciona el error de conversión
            $table->dateTimeTz('created_at', 7)->nullable();
            $table->dateTimeTz('updated_at', 7)->nullable();
        });

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_role
            CHECK (role IN ('admin', 'secretaria', 'secretaria_talleres', 'uti'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};