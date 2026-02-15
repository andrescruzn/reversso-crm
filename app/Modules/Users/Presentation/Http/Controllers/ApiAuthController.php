<?php

declare(strict_types=1);

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Common\Http\Responses\ApiResponse;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiAuthController extends Controller
{
    /**
     * Login JWT: devuelve token.
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $token = Auth::guard('api')->attempt($credentials);

        if (!$token) {
            return ApiResponse::unauthorized('Credenciales incorrectas.');
        }

        $user = Auth::guard('api')->user();

        if (!$user->isActive()) {
            Auth::guard('api')->logout();
            return ApiResponse::forbidden('Usuario inactivo.');
        }

        return $this->respondWithToken((string) $token);
    }

    /**
     * Registro: crea usuario, asigna rol, devuelve token.
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'nullable|string',
        ]);

        try {
            $user = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name'      => $validated['name'],
                    'email'     => $validated['email'],
                    'password'  => $validated['password'],
                    'is_active' => true,
                ]);

                $roleName = $validated['role'] ?? 'conductor';
                $role = Role::where('name', $roleName)
                    ->orWhere('display_name', $roleName)
                    ->first();

                if ($role) {
                    $user->assignRole($role);
                }

                return $user;
            });

            $token = Auth::guard('api')->login($user);

            return $this->respondWithToken((string) $token, 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Error en el registro: ' . $e->getMessage(), 1100, 500);
        }
    }

    /**
     * Datos del usuario autenticado.
     * GET /api/v1/auth/me
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return ApiResponse::success([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->map(fn ($r) => [
                'name'         => $r->name,
                'display_name' => $r->display_name,
            ]),
            'is_active' => $user->is_active,
        ], 'Usuario autenticado');
    }

    /**
     * Cerrar sesion JWT.
     * POST /api/v1/auth/logout
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return ApiResponse::success(message: 'Sesion cerrada correctamente.');
    }

    /**
     * Refrescar token JWT.
     * POST /api/v1/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        $token = Auth::guard('api')->refresh();

        return $this->respondWithToken((string) $token);
    }

    /**
     * Respuesta estandar con token.
     */
    private function respondWithToken(string $token, int $statusCode = 200): JsonResponse
    {
        return ApiResponse::success([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
        ], 'Autenticacion exitosa', $statusCode);
    }
}
