<?php

declare(strict_types=1);

namespace App\Common\Http\Middleware;

use App\Common\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware para verificar que el usuario tenga un rol específico.
 * Soporta respuestas JSON para API y redirecciones para Web Responsive.
 */
class RoleMiddleware
{
    /**
     * Manejar la petición entrante.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // =====================================================================
        // 1. VERIFICAR AUTENTICACIÓN
        // =====================================================================
        if (!Auth::check()) {
            return $request->expectsJson()
                ? ApiResponse::unauthorized('Debes iniciar sesión')
                : redirect()->route('login');
        }

        // =====================================================================
        // 2. OBTENER USUARIO AUTENTICADO
        // =====================================================================
        $user = Auth::user();

        // =====================================================================
        // 3. VERIFICAR ROLES (Usa hasAnyRole definido en tu modelo User)
        // =====================================================================
        // Nota: Asegúrate de que el método en el modelo User acepte arrays.
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        // =====================================================================
        // 4. ACCESO DENEGADO (Lógica Híbrida CRM)
        // =====================================================================
        if ($request->expectsJson()) {
            return ApiResponse::forbidden('No tienes permisos para acceder a este recurso');
        }

        // Redirección amigable para el conductor en la Web App
        return redirect()->route('dashboard')
            ->with('error', 'No tienes permisos para acceder a esta sección.');
    }
}
