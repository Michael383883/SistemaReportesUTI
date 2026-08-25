<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('CATEGORIAS_CLASIFICACION', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('NOMBRE', 60);
            $table->string('TIPO', 20); // 'DOCUMENTO' | 'TITULO'
            $table->timestamp('FECHA_REGISTRO')->useCurrent();

            $table->unique(['NOMBRE', 'TIPO']);
        });

        // Sembrar categorías de DOCUMENTO desde lo ya usado en CLASIFICACION_DOCUMENTO
        $categoriasDoc = DB::table('CLASIFICACION_DOCUMENTO')
            ->select('CATEGORIA')
            ->whereNotNull('CATEGORIA')
            ->distinct()
            ->pluck('CATEGORIA');

        foreach ($categoriasDoc as $nombre) {
            $nombre = trim($nombre);
            if ($nombre === '')
                continue;

            DB::table('CATEGORIAS_CLASIFICACION')->insert([
                'NOMBRE' => $nombre,
                'TIPO' => 'DOCUMENTO',
            ]);
        }

        // Sembrar tipos de TITULO: base + lo ya usado en CLASIFICACION_TITULO
        $tiposBase = ['DIPLOMADO', 'ESPECIALIDAD', 'MAESTRÍA', 'DOCTORADO'];

        $tiposExistentes = DB::table('CLASIFICACION_TITULO')
            ->select('TIPO_TITULO')
            ->whereNotNull('TIPO_TITULO')
            ->distinct()
            ->pluck('TIPO_TITULO')
            ->map(fn($t) => mb_strtoupper(trim($t)))
            ->toArray();

        $todosLosTipos = array_unique(array_merge($tiposBase, $tiposExistentes));

        foreach ($todosLosTipos as $nombre) {
            if ($nombre === '')
                continue;

            DB::table('CATEGORIAS_CLASIFICACION')->insert([
                'NOMBRE' => $nombre,
                'TIPO' => 'TITULO',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('CATEGORIAS_CLASIFICACION');
    }
};