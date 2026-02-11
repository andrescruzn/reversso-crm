<?php

declare(strict_types=1);

namespace App\Common\Services;

/**
 * Resultado de operaciones en la capa de servicios.
 *
 * PATRÓN: Result Object
 *
 * RESPONSABILIDAD:
 * Encapsular el resultado de una operación (éxito o fallo) sin lanzar excepciones.
 * Los Services retornan este objeto y los Controllers lo transforman en respuestas HTTP.
 */
final class ServiceResult
{
    // =====================================================================
    // CONSTRUCTOR PRIVADO (Factory Pattern)
    // =====================================================================

    private function __construct(
        public readonly bool $success,
        public readonly mixed $data,
        public readonly string $message,
        public readonly int $errorCode
    ) {}

    // =====================================================================
    // FACTORY METHODS
    // =====================================================================

    /**
     * Crear resultado exitoso.
     */
    public static function ok(
        mixed $data = null,
        string $message = 'Operación exitosa'
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            errorCode: 0
        );
    }

    /**
     * Crear resultado fallido.
     */
    public static function fail(
        string $message,
        int $errorCode = 1000
    ): self {
        return new self(
            success: false,
            data: null,
            message: $message,
            errorCode: $errorCode
        );
    }

    // =====================================================================
    // MÉTODOS DE VERIFICACIÓN
    // =====================================================================

    /**
     * Verificar si el resultado es exitoso.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Verificar si el resultado falló.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Transformar a array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'errorCode' => $this->errorCode,
        ];
    }
}
