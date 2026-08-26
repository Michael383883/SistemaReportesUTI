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
            $table->string('TIPO', 20); // 'DOCUMENTO' | 'TITULO' | 'KARDEX'
            $table->timestamp('FECHA_REGISTRO')->useCurrent();

            $table->unique(['NOMBRE', 'TIPO']);
        });

        // ── DOCUMENTO: sembrar desde lo ya usado en CLASIFICACION_DOCUMENTO ──
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

        // ── TITULO: base + lo ya usado en CLASIFICACION_TITULO ──
        $tiposBase = ['DIPLOMADO', 'ESPECIALIDAD', 'MAESTRÍA', 'DOCTORADO'];

        $tiposExistentes = DB::table('CLASIFICACION_TITULO')
            ->select('TIPO_TITULO')
            ->whereNotNull('TIPO_TITULO')
            ->distinct()
            ->pluck('TIPO_TITULO')
            ->map(fn($t) => mb_strtoupper(trim($t)))
            ->toArray();

        $todosLosTiposTitulo = array_unique(array_merge($tiposBase, $tiposExistentes));

        foreach ($todosLosTiposTitulo as $nombre) {
            if ($nombre === '')
                continue;

            DB::table('CATEGORIAS_CLASIFICACION')->insert([
                'NOMBRE' => $nombre,
                'TIPO' => 'TITULO',
            ]);
        }

        // ── KARDEX: base (para que NUNCA aparezca vacío la primera vez) +
        //    lo que ya esté en uso en las tablas de Tipo de Ingreso.
        //
        //    ¡OJO! Acá se asume que el "tipo_ingreso" que se edita en
        //    Kardex/TipoIngresoTabla vive en GRUPO.TIPO_INGRESO y en
        //    DETALLE_RESOLUCION.TIPO_INGRESO (según lo que ya reporta
        //    aplicarCambios: "actualizados en grupos" / "actualizados en
        //    detalle de resolución"). Si en tu esquema real los nombres de
        //    tabla/columna son otros, ajustalos acá abajo — el resto de la
        //    migración no depende de esto.
        $kardexBase = [
            'DOCENTES TITULARES',
            'DOCENTES TEMPORALES',
            'EXAMEN DE SUFICIENCIA',
            'ACEFALA',
            'SIN EXAMEN DE SUFICIENCIA',
        ];

        $kardexExistentes = collect();

        if (Schema::hasTable('GRUPOS') && Schema::hasColumn('GRUPOS', 'TIPO_INGRESO')) {
            $kardexExistentes = $kardexExistentes->merge(
                DB::table('GRUPOS')
                    ->select('TIPO_INGRESO')
                    ->whereNotNull('TIPO_INGRESO')
                    ->distinct()
                    ->pluck('TIPO_INGRESO')
            );
        }

        if (Schema::hasTable('RESOLUCION_DETALLE') && Schema::hasColumn('RESOLUCION_DETALLE', 'TIPO_INGRESO')) {
            $kardexExistentes = $kardexExistentes->merge(
                DB::table('RESOLUCION_DETALLE')
                    ->select('TIPO_INGRESO')
                    ->whereNotNull('TIPO_INGRESO')
                    ->distinct()
                    ->pluck('TIPO_INGRESO')
            );
        }

        $kardexExistentes = $kardexExistentes
            ->map(fn($t) => mb_strtoupper(trim($t)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $todosLosKardex = array_unique(array_merge($kardexBase, $kardexExistentes));

        foreach ($todosLosKardex as $nombre) {
            if ($nombre === '')
                continue;

            DB::table('CATEGORIAS_CLASIFICACION')->insert([
                'NOMBRE' => $nombre,
                'TIPO' => 'KARDEX',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('CATEGORIAS_CLASIFICACION');
    }
};