<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('module_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);
            $table->string('role', 50);
            $table->boolean('enabled')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['module', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_permissions');
    }
};