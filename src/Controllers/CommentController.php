<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\CommentRepository;
use App\Repositories\TaskRepository;

/*
 * Routes protégées par AuthMiddleware + ProjectAccessMiddleware : l'utilisateur est membre du projet {id}.
 *
 * Règles métier à respecter :
 * - la tâche {taskId} doit appartenir au projet {id} (sinon 404 TASK_NOT_FOUND) ;
 * - le commentaire {commentId} doit appartenir à la tâche {taskId} (sinon 404 COMMENT_NOT_FOUND) ;
 * - tout membre du projet peut lire et ajouter des commentaires ;
 * - seul l'auteur peut modifier son commentaire ;
 * - l'auteur ou un administrateur du projet peut supprimer un commentaire.
 *
 * Validation du contenu : voir Validator::comment.
 */
final class CommentController extends Controller
{
    public function __construct(
        private readonly CommentRepository $comments,
        private readonly TaskRepository $tasks,
    ) {
    }

    /** GET /projects/{id}/tasks/{taskId}/comments — TODO (étudiant) : 200 ['comments' => ...] (ordre chronologique) */
    public function index(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    /** GET /projects/{id}/tasks/{taskId}/comments/{commentId} — TODO (étudiant) : 200 ['comment' => ...] */
    public function show(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    /** POST /projects/{id}/tasks/{taskId}/comments — TODO (étudiant) : 201 ['comment' => ...] */
    public function store(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    /** PUT /projects/{id}/tasks/{taskId}/comments/{commentId} — TODO (étudiant) : 200 ['comment' => ...] */
    public function update(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    /** DELETE /projects/{id}/tasks/{taskId}/comments/{commentId} — TODO (étudiant) : 200 */
    public function destroy(Request $request): JsonResponse
    {
        throw $this->notImplemented();
    }

    private function notImplemented(): HttpException
    {
        return new HttpException(501, 'Fonctionnalité à implémenter', 'NOT_IMPLEMENTED');
    }
}
