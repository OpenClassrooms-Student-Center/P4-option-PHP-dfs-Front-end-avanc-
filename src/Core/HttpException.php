<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Erreur HTTP "métier" : elle est transformée en réponse JSON
 * { success: false, message, error, data? } par le point d'entrée.
 */
class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly string $errorCode,
        public readonly ?array $data = null,
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $message, string $code = 'BAD_REQUEST'): self
    {
        return new self(400, $message, $code);
    }

    public static function unauthorized(string $message = 'Non autorisé'): self
    {
        return new self(401, $message, 'UNAUTHORIZED');
    }

    public static function forbidden(string $message = 'Accès refusé'): self
    {
        return new self(403, $message, 'FORBIDDEN');
    }

    public static function notFound(string $message, string $code = 'NOT_FOUND'): self
    {
        return new self(404, $message, $code);
    }

    public static function conflict(string $message, string $code): self
    {
        return new self(409, $message, $code);
    }

    /**
     * @param list<array{field: string, message: string}> $errors
     */
    public static function validation(string $message, array $errors): self
    {
        return new self(400, $message, 'VALIDATION_ERROR', ['errors' => $errors]);
    }
}
