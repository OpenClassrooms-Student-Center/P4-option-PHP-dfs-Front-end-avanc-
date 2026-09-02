<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Réponse JSON au format commun de l'API : { success, message, data?, error? }
 */
class JsonResponse
{
    public function __construct(
        public readonly int $status,
        public readonly array $payload,
    ) {
    }

    public static function success(string $message, ?array $data = null, int $status = 200): self
    {
        $payload = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }

        return new self($status, $payload);
    }

    public static function error(int $status, string $message, string $errorCode, ?array $data = null): self
    {
        $payload = ['success' => false, 'message' => $message, 'error' => $errorCode];
        if ($data !== null) {
            $payload['data'] = $data;
        }

        return new self($status, $payload);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
