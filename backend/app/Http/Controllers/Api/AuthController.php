<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * HU1 — Inicio de sesión con roles
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas. Verifique su correo y contraseña.',
            ], 401);
        }

        $user = Auth::user();

        // Verifica que el usuario esté activo
        if (! $user->active) {
            Auth::logout();
            return response()->json([
                'message' => 'Su cuenta está desactivada. Contacte al administrador.',
            ], 403);
        }

        // Revoca tokens anteriores (sesión única)
        $user->tokens()->delete();

        // Crea token con nombre descriptivo del rol
        $token = $user->createToken("uti-fce-{$user->role}")->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    /**
     * HU2 — Cerrar sesión
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoca solo el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * HU1 — Obtener usuario autenticado actual
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    // ── Formato consistente del usuario hacia el frontend ────────
    private function formatUser($user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'active' => $user->active,
        ];
    }
}
