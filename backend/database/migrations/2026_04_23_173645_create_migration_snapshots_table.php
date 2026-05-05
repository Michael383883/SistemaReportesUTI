<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // database/migrations/xxxx_create_migration_snapshots_table.php
        Schema::create('migration_snapshots', function (Blueprint $table) {
            $table->string('tabla', 100);
            $table->string('row_key', 500);      // identificador de fila (hash de todas las columnas como key)
            $table->string('row_hash', 32);      // MD5 del contenido
            $table->timestamp('synced_at')->useCurrent();
            $table->primary(['tabla', 'row_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_snapshots');
    }
};
