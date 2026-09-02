<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\UserRepository;

final class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** GET /users/search?query=... (autocomplete des contributeurs) */
    public function search(Request $request): JsonResponse
    {
        $query = is_string($request->query['query'] ?? null) ? trim($request->query['query']) : '';

        if (mb_strlen($query) < 2) {
            throw HttpException::badRequest('La recherche doit contenir au moins 2 caractères', 'INVALID_QUERY');
        }

        return JsonResponse::success('Utilisateurs trouvés', ['users' => $this->users->search($query)]);
    }
}
