<?php

declare(strict_types=1);

namespace App\Common\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) return redirect()->route('login');

        // Convertimos todos los roles que llegan de la ruta a minúsculas
        $requestedRoles = array_map('strtolower', $roles);

        // Obtenemos los roles del usuario en minúsculas
        $userRoles = $user->roles()->pluck('display_name')->map(fn($role) => strtolower($role))->toArray();

        // Verificamos si hay alguna coincidencia
        $hasPermission = !empty(array_intersect($requestedRoles, $userRoles));

        if ($hasPermission) {
            return $next($request);
        }

        abort(403, "Acceso denegado. Se requiere uno de estos roles: " . implode(', ', $roles));
    }
}
