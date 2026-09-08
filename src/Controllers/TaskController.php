<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;

/*
 * Routes protégées par AuthMiddleware + ProjectAccessMiddleware :
 * l'utilisateur est membre du projet {id}.
 *
 * Règles métier à respecter : voir le README (section "Droits d'accès").
 */
final class TaskController extends Controller
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectRepository $projects,
    ) {
    }

    /**
     * POST /projects/{id}/tasks
     *
     * TODO (étudiant) :
     * - valider le corps de la requête (voir Validator::createTask) ;
     * - vérifier que les utilisateurs de assigneeIds sont membres du projet (propriétaire inclus),
     *   sinon répondre 400 INVALID_ASSIGNEES ;
     * - créer la tâche ET ses assignations de manière atomique ;
     * - répondre 201 avec ['task' => ...] (créateur, projet, assignations et commentaires inclus).
     */
    public function store(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    /**
     * PUT /projects/{id}/tasks/{taskId}  (mise à jour partielle)
     *
     * TODO (étudiant) :
     * - valider le corps de la requête (voir Validator::updateTask) ;
     * - répondre 404 TASK_NOT_FOUND si la tâche n'appartient pas au projet {id} ;
     * - si assigneeIds est fourni, il remplace la liste des assignations existantes ;
     * - répondre 200 avec ['task' => ...].
     */
    public function update(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    /**
     * DELETE /projects/{id}/tasks/{taskId}
     *
     * TODO (étudiant) :
     * - répondre 404 TASK_NOT_FOUND si la tâche n'appartient pas au projet {id} ;
     * - supprimer la tâche (assignations et commentaires sont supprimés en cascade).
     */
    public function destroy(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    private function notImplemented(): HttpException
    {
        return new HttpException(501, 'Fonctionnalité à implémenter', 'NOT_IMPLEMENTED');
    }
}
