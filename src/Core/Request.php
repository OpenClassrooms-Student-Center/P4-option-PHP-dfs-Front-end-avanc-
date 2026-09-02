<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Représentation simplifiée de la requête HTTP entrante.
 */
class Request
{
    /** Paramètres extraits de l'URL (ex. {id}, {taskId}) */
    public array $params = [];

    /** Données ajoutées par les middlewares (utilisateur connecté, droits sur le projet...) */
    public array $attributes = [];

    private ?array $body = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly array $headers,
        private readonly string $rawBody,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rtrim($path, '/') ?: '/',
            $_GET,
            $headers,
            (string) file_get_contents('php://input'),
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function param(string $name): string
    {
        return $this->params[$name] ?? '';
    }

    /**
     * Corps JSON décodé (tableau vide si aucun corps).
     */
    public function body(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }
        if (trim($this->rawBody) === '') {
            return $this->body = [];
        }

        $decoded = json_decode($this->rawBody, true);
        if (!is_array($decoded)) {
            throw HttpException::badRequest("Le corps de la requête n'est pas un JSON valide", 'INVALID_JSON');
        }

        return $this->body = $decoded;
    }

    /**
     * Valeur d'un champ du corps JSON (null si absent).
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->body()) ? $this->body()[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body());
    }

    /**
     * Utilisateur connecté, renseigné par AuthMiddleware.
     * @return array{id: string, email: string, name: ?string}
     */
    public function user(): array
    {
        return $this->attributes['user'];
    }
}
