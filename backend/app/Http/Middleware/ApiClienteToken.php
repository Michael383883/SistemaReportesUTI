<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiClienteToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?: $request->header('X-API-KEY');

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token requerido'], 401);
        }

        $cliente = DB::table('API_CLIENTES')
            ->where('TOKEN_HASH', hash('sha256', $token))
            ->where('ACTIVO', 1)
            ->first();

        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'Token inválido'], 401);
        }

        DB::table('API_CLIENTES')
            ->where('ID', $cliente->ID)
            ->update(['ULTIMO_USO' => now()->format('Y-m-d H:i:s')]);

        return $next($request);
    }
}