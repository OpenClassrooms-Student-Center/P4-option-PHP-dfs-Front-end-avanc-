<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\JwtService;
use App\Services\Validator;

final class AuthController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwt,
    ) {
    }

    /** POST /auth/register */
    public function register(Request $request): JsonResponse
    {
        $data = $request->body();
        $this->assertValid(Validator::register($data), "Données d'inscription invalides");

        if ($this->users->emailExists($data['email'])) {
            throw HttpException::conflict('Un utilisateur avec cet email existe déjà', 'EMAIL_ALREADY_EXISTS');
        }

        $user = $this->users->create(
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $this->trimOrNull($data['name'] ?? null),
        );

        return JsonResponse::success('Utilisateur créé avec succès', [
            'user' => $user,
            'token' => $this->jwt->generate($user['id'], $user['email']),
        ], 201);
    }

    /** POST /auth/login */
    public function login(Request $request): JsonResponse
    {
        $data = $request->body();
        $this->assertValid(Validator::login($data), 'Données de connexion invalides');

        $user = $this->users->findCredentialsByEmail((string) $data['email']);

        // Même message dans les deux cas pour ne pas révéler si l'email existe
        if ($user === null || !password_verify((string) $data['password'], $user['password'])) {
            throw new HttpException(401, 'Email ou mot de passe incorrect', 'INVALID_CREDENTIALS');
        }

        unset($user['password']);

        return JsonResponse::success('Connexion réussie', [
            'user' => $user,
            'token' => $this->jwt->generate($user['id'], $user['email']),
        ]);
    }

    /** GET /auth/profile */
    public function profile(Request $request): JsonResponse
    {
        return JsonResponse::success('Profil récupéré avec succès', [
            'user' => $this->users->findProfileById($request->user()['id']),
        ]);
    }

    /** PUT /auth/profile */
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->body();
        $this->assertValid(Validator::updateProfile($data), 'Données de mise à jour invalides');

        $fields = [];
        if (array_key_exists('name', $data)) {
            $fields['name'] = trim($data['name']);
        }
        if (array_key_exists('email', $data)) {
            $fields['email'] = strtolower($data['email']);
            if ($fields['email'] !== $request->user()['email'] && $this->users->emailExists($fields['email'])) {
                throw HttpException::conflict('Un utilisateur avec cet email existe déjà', 'EMAIL_ALREADY_EXISTS');
            }
        }

        return JsonResponse::success('Profil mis à jour avec succès', [
            'user' => $this->users->updateProfile($request->user()['id'], $fields),
        ]);
    }

    /** PUT /auth/password */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->body();
        $this->assertValid(Validator::updatePassword($data), 'Données de mise à jour du mot de passe invalides');

        $userId = $request->user()['id'];
        if (!password_verify((string) $data['currentPassword'], (string) $this->users->findPasswordHash($userId))) {
            throw new HttpException(401, 'Mot de passe actuel incorrect', 'INVALID_CURRENT_PASSWORD');
        }

        $this->users->updatePassword($userId, password_hash($data['newPassword'], PASSWORD_BCRYPT));

        return JsonResponse::success('Mot de passe mis à jour avec succès');
    }
}
