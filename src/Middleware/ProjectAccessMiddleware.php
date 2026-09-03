<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Repositories\ProjectRepository;

/**
 * Vérifie que le projet {id} existe et que l'utilisateur connecté en est membre.
 * Les droits sont ensuite disponibles dans $request->attributes['projectAccess'].
 * À placer après AuthMiddleware.
 */
final class ProjectAccessMiddleware
{
    public function __construct(private readonly ProjectRepository $projects)
    {
    }

    public function __invoke(Request $request): void
    {
        $access = $this->projects->findAccess($request->param('id'), $request->user()['id']);

        if ($access === null) {
            throw HttpException::notFound('Projet non trouvé', 'PROJECT_NOT_FOUND');
        }
        if (!$access->hasAccess()) {
            throw HttpException::forbidden('Accès refusé au projet');
        }

        $request->attributes['projectAccess'] = $access;
    }
}
