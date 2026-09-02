<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Services\ProjectAccess;

/**
 * Méthodes utilitaires communes aux contrôleurs.
 */
abstract class Controller
{
    /**
     * Lève une erreur 400 VALIDATION_ERROR si la liste d'erreurs n'est pas vide.
     */
    protected function assertValid(array $errors, string $message): void
    {
        if ($errors !== []) {
            throw HttpException::validation($message, $errors);
        }
    }

    /**
     * Droits de l'utilisateur sur le projet {id}, calculés par ProjectAccessMiddleware.
     */
    protected function projectAccess(Request $request): ProjectAccess
    {
        return $request->attributes['projectAccess'];
    }

    /**
     * Chaîne nettoyée, ou null si elle est vide.
     */
    protected function trimOrNull(mixed $value): ?string
    {
        $trimmed = is_string($value) ? trim($value) : '';

        return $trimmed === '' ? null : $trimmed;
    }
}
