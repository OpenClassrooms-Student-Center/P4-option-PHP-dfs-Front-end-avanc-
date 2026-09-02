<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\JwtService;

/**
 * Vérifie le header "Authorization: Bearer <token>".
 * Si le token est valide, l'utilisateur connecté est disponible via $request->user().
 */
final class AuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly UserRepository $users,
    ) {
    }

    public function __invoke(Request $request): void
    {
        $header = $request->header('Authorization') ?? '';
        if (!preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
            throw HttpException::unauthorized("Token d'authentification manquant");
        }

        $payload = $this->jwt->verify($matches[1]);
        $user = $this->users->findPublicById((string) ($payload['userId'] ?? ''));

        if ($user === null) {
            throw HttpException::unauthorized('Utilisateur non trouvé');
        }

        $request->attributes['user'] = $user;
    }
}
