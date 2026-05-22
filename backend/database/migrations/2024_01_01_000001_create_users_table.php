<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ╔══════════════════════════════════════════════════════════════╗
// ║  INSTRUCCIÓN: Reemplaza la migration de users existente      ║
// ║  o créala como nueva:                                        ║
// ║  php artisan make:migration create_users_table               ║
// ╚══════════════════════════════════════════════════════════════╝

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // Roles del sistema: admin | secretaria | secretaria_talleres | uti
            $table->enum('role', ['admin', 'secretaria', 'secretaria_talleres', 'uti'])->default('uti');
            $table->boolean('active')->default(true);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};