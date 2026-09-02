<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Création et vérification des tokens JWT (algorithme HS256).
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds,
    ) {
    }

    public function generate(string $userId, string $email): string
    {
        $now = time();

        return JWT::encode([
            'userId' => $userId,
            'email' => $email,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ], $this->secret, self::ALGORITHM);
    }

    /**
     * @return array{userId: string, email: string}
     */
    public function verify(string $token): array
    {
        try {
            return (array) JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (ExpiredException) {
            throw HttpException::unauthorized('Token expiré');
        } catch (Throwable) {
            throw HttpException::unauthorized('Token invalide');
        }
    }
}
