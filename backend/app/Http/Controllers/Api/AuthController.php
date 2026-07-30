<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas. Verifique su correo y contraseña.',
            ], 401);
        }

        $user = Auth::user();

        if (!$user->active) {
            Auth::logout();
            return response()->json([
                'message' => 'Su cuenta está desactivada. Contacte al administrador.',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken("uti-fce-{$user->role}")->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->formatUser($request->user())]);
    }

    /**
     * POST /api/auth/verify-password
     *
     * Verifica la contraseña actual del usuario autenticado SIN crear un token
     * nuevo ni tocar la sesión activa (a diferencia de login(), que sí crea token).
     * Se usa como paso previo obligatorio antes de permitir el cambio de contraseña,
     * incluyendo el caso de un administrador que cambia su propia contraseña.
     */
    public function verifyPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json([
                'message' => 'La contraseña ingresada es incorrecta.',
            ], 422);
        }

        return response()->json([
            'message' => 'Contraseña verificada correctamente.',
            'verified' => true,
        ]);
    }

    /**
     * PUT /api/auth/change-password
     *
     * Cambia la contraseña del usuario autenticado. Vuelve a validar la
     * contraseña actual como defensa en profundidad, aunque el frontend ya
     * la haya verificado antes con verifyPassword().
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
        ]);

        $user = $request->user();

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
            ], 422);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        // Invalida el resto de tokens activos por seguridad, deja solo el actual
        $user->tokens()
            ->where('id', '!=', optional($request->user()->currentAccessToken())->id)
            ->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    private function formatUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'active' => (bool) $user->active,
        ];
    }
}