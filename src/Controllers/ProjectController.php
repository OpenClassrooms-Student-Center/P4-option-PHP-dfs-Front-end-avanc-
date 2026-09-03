<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\Validator;

/*
 * Les routes /projects/{id}... passent par ProjectAccessMiddleware :
 * le projet existe et l'utilisateur en est membre.
 */
final class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly UserRepository $users,
        private readonly TaskRepository $tasks,
    ) {
    }

    /** GET /projects */
    public function index(Request $request): JsonResponse
    {
        return JsonResponse::success('Projets récupérés avec succès', [
            'projects' => $this->projects->findAllForUser($request->user()['id']),
        ]);
    }

    /** POST /projects */
    public function store(Request $request): JsonResponse
    {
        $data = $request->body();
        $this->assertValid(Validator::createProject($data), 'Données de création de projet invalides');

        $userId = $request->user()['id'];
        // Les contributeurs sont identifiés par email ; le créateur est exclu (il est propriétaire)
        $contributorIds = array_values(array_diff(
            $this->users->findIdsByEmails($data['contributors'] ?? []),
            [$userId],
        ));

        $project = $this->projects->create(
            $userId,
            trim($data['name']),
            $this->trimOrNull($data['description'] ?? null),
            $contributorIds,
        );

        return JsonResponse::success('Projet créé avec succès', ['project' => $project + ['userRole' => 'ADMIN']], 201);
    }

    /** GET /projects/{id} */
    public function show(Request $request): JsonResponse
    {
        $projectId = $request->param('id');
        $project = $this->projects->findById($projectId);
        $project['tasks'] = $this->tasks->findWithCreatorByProject($projectId);
        $project['userRole'] = $this->projectAccess($request)->role;

        return JsonResponse::success('Projet récupéré avec succès', ['project' => $project]);
    }

    /** PUT /projects/{id} */
    public function update(Request $request): JsonResponse
    {
        $access = $this->projectAccess($request);
        if (!$access->canModifyProject()) {
            throw HttpException::forbidden("Vous n'avez pas les permissions pour modifier ce projet");
        }

        $data = $request->body();
        $this->assertValid(Validator::updateProject($data), 'Données de mise à jour invalides');

        $fields = [];
        if (array_key_exists('name', $data)) {
            $fields['name'] = trim($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $fields['description'] = $this->trimOrNull($data['description']);
        }

        $project = $this->projects->update($request->param('id'), $fields);

        return JsonResponse::success('Projet mis à jour avec succès', [
            'project' => $project + ['userRole' => $access->role],
        ]);
    }

    /** DELETE /projects/{id} */
    public function destroy(Request $request): JsonResponse
    {
        if (!$this->projectAccess($request)->canDeleteProject()) {
            throw HttpException::forbidden('Seul le propriétaire peut supprimer ce projet');
        }

        $this->projects->delete($request->param('id'));

        return JsonResponse::success('Projet supprimé avec succès');
    }

    /** POST /projects/{id}/contributors */
    public function addContributor(Request $request): JsonResponse
    {
        if (!$this->projectAccess($request)->canModifyProject()) {
            throw HttpException::forbidden("Vous n'avez pas les permissions pour modifier ce projet");
        }

        $data = $request->body();
        $this->assertValid(Validator::contributor($data), 'Données de contributeur invalides');

        $projectId = $request->param('id');
        $user = $this->users->findPublicByEmail($data['email']);
        if ($user === null) {
            throw HttpException::notFound('Utilisateur non trouvé', 'USER_NOT_FOUND');
        }
        if ($this->projects->isOwnerOrMember($projectId, $user['id'])) {
            throw HttpException::conflict("L'utilisateur est déjà membre de ce projet", 'USER_ALREADY_MEMBER');
        }

        $member = $this->projects->addMember($projectId, $user['id'], $data['role'] ?? 'CONTRIBUTOR');

        return JsonResponse::success('Contributeur ajouté avec succès', ['member' => $member], 201);
    }

    /** DELETE /projects/{id}/contributors/{userId} */
    public function removeContributor(Request $request): JsonResponse
    {
        if (!$this->projectAccess($request)->canModifyProject()) {
            throw HttpException::forbidden("Vous n'avez pas les permissions pour modifier ce projet");
        }

        if ($this->projects->removeMember($request->param('id'), $request->param('userId')) === 0) {
            throw HttpException::notFound("Cet utilisateur n'est pas contributeur du projet", 'MEMBER_NOT_FOUND');
        }

        return JsonResponse::success('Contributeur retiré avec succès');
    }
}
