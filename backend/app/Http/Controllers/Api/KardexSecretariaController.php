<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModulePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KardexSecretariaController extends Controller
{
    public function estado(Request $request): JsonResponse
    {
        return response()->json([
            'habilitado' => ModulePermission::habilitado('kardex', $request->user()->role ?? null),
        ]);
    }

    public function docentes(): JsonResponse
    {
        // Si el texto ya es UTF-8 válido se deja igual; si no, se asume Windows-1252
        $utf8 = fn($s) => mb_check_encoding((string) $s, 'UTF-8')
            ? trim((string) $s)
            : trim(mb_convert_encoding((string) $s, 'UTF-8', 'Windows-1252'));

        $filas = DB::connection('sqlsrv')
            ->table('DOCENTES')
            ->select('CODIGO', 'APELLIDOS', 'NOMBRES')
            ->orderBy('APELLIDOS')
            ->orderBy('NOMBRES')
            ->get()
            ->map(fn($d) => [
                'codigo' => trim((string) $d->CODIGO),
                'apellidos' => $utf8($d->APELLIDOS),
                'nombres' => $utf8($d->NOMBRES),
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $filas]);
    }
    
    public function reporte(string $codigo): JsonResponse
    {
        if (!ctype_digit($codigo)) {
            return response()->json(['success' => false, 'message' => 'Código de docente inválido'], 422);
        }

        $interna = Request::create('/interno', 'POST', ['docente' => $codigo]);
        $respuesta = app(ReporteDocenteController::class)->materiasDictadas($interna);

        if ($respuesta->getStatusCode() !== 200) {
            return $respuesta;
        }

        $data = $respuesta->getData(true);

        $materias = array_map(fn($m) => [
            'nro' => $m['nro'] ?? null,
            'gestion' => $m['gestion'] ?? '',
            'anio' => $m['ANIO'] ?? null,
            'periodo' => $m['PERIODO'] ?? null,
            'plan' => $m['PLAN'] ?? '',
            'materia' => $m['materia'] ?? '',
            'materia_codigo' => $m['materia_codigo'] ?? '',
            'compartido' => $m['compartido'] ?? '',
            'comp' => $m['comp'] ?? '',
            'orden_comparte' => $m['orden_comparte'] ?? null,
            'grp' => $m['grp'] ?? '',
        ], $data['materias'] ?? []);

        return response()->json([
            'success' => true,
            'docente' => $data['docente'] ?? null,
            'total' => count($materias),
            'materias' => $materias,
        ]);
    }
}