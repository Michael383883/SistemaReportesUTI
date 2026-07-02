<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CLASIFICACION_DOCENTE', function (Blueprint $table) {
            $table->integer('ORDEN_NIVEL')->nullable()->after('NIVEL');
        });
    }

    public function down(): void
    {
        Schema::table('CLASIFICACION_DOCENTE', function (Blueprint $table) {
            $table->dropColumn('ORDEN_NIVEL');
        });
    }
};