<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MigracionService;

class MigracionController extends Controller
{
    public function copiarTablas(Request $request)
    {
        $request->validate([

            'tablas' => 'required|array',
            'tablas.*' => 'string'

        ]);

        $resultado = MigracionService::copiarTablas(
            $request->tablas
        );

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function syncTable(Request $request)
    {
        $request->validate([

            'tabla' => 'required|string',
            'pk' => 'required|string'

        ]);

        $resultado = MigracionService::syncTable(
            $request->tabla,
            $request->pk
        );

        return response()->json($resultado);
    }
}