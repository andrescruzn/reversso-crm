<?php

declare(strict_types=1);

namespace App\Common\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Helper para generar respuestas estándar de la API.
 *
 * PATRÓN: Response Builder
 *
 * ESTRUCTURA ESTÁNDAR:
 * {
 *   "msg": "Mensaje descriptivo",
 *   "errorCode": 0 (éxito) o >0 (error),
 *   "data": {...} o null
 * }
 */
final class ApiResponse
{
    /**
     * Respuesta exitosa estándar.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operación exitosa',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'msg' => $message,
            'errorCode' => 0,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Respuesta de creación exitosa.
     */
    public static function created(
        mixed $data = null,
        string $message = 'Recurso creado exitosamente'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    /**
     * Respuesta con paginación estándar.
     */
    public static function paginated(
        iterable $items,
        int $total,
        int $page,
        int $limit,
        string $message = 'Datos obtenidos exitosamente'
    ): JsonResponse {
        return self::success([
            'items' => $items,
            'paginate' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ], $message);
    }

    /**
     * Respuesta de error estándar.
     */
    public static function error(
        string $message,
        int $errorCode = 1000,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'msg' => $message,
            'errorCode' => $errorCode,
            'data' => null,
        ], $statusCode);
    }

    /**
     * Respuesta de validación fallida.
     */
    public static function validationError(
        array $errors,
        string $message = 'Errores de validación'
    ): JsonResponse {
        return response()->json([
            'msg' => $message,
            'errorCode' => 1001,
            'data' => ['errors' => $errors],
        ], 422);
    }

    /**
     * Respuesta de no autorizado.
     */
    public static function unauthorized(
        string $message = 'No autorizado'
    ): JsonResponse {
        return self::error($message, 1003, 401);
    }

    /**
     * Respuesta de prohibido.
     */
    public static function forbidden(
        string $message = 'Acceso prohibido'
    ): JsonResponse {
        return self::error($message, 1004, 403);
    }

    /**
     * Respuesta de recurso no encontrado.
     */
    public static function notFound(
        string $message = 'Recurso no encontrado'
    ): JsonResponse {
        return self::error($message, 1002, 404);
    }
}
