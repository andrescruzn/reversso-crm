<?php

declare(strict_types=1);

namespace App\Common\Http\Middleware;

use App\Common\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar roles.
 * Soporta JSON (API) y redirecciones (Web).
 * Compara contra name Y display_name (case-insensitive).
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return $request->expectsJson()
                ? ApiResponse::unauthorized('Debes iniciar sesión')
                : redirect()->route('login');
        }

        $user = Auth::user();

        // Normalizar roles solicitados a minúsculas
        $requested = array_map('strtolower', $roles);

        // Obtener roles del usuario (name y display_name) en minúsculas
        $userRoleNames = $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->toArray();
        $userRoleDisplayNames = $user->roles->pluck('display_name')->map(fn ($r) => strtolower($r))->toArray();
        $allUserRoles = array_unique(array_merge($userRoleNames, $userRoleDisplayNames));

        if (!empty(array_intersect($requested, $allUserRoles))) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return ApiResponse::forbidden('No tienes permisos para acceder a este recurso');
        }

        return redirect()->route('dashboard')
            ->with('error', 'No tienes permisos para acceder a esta sección.');
    }
}
