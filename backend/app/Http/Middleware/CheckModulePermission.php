<?php

namespace App\Http\Middleware;

use App\Models\ModulePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user || !ModulePermission::habilitado($module, $user->role ?? null)) {
            return response()->json([
                'success' => false,
                'code' => 'MODULE_DISABLED',
                'message' => 'Este módulo no está habilitado para tu rol.',
            ], 403);
        }

        return $next($request);
    }
}