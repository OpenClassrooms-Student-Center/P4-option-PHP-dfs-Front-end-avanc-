<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Droits de l'utilisateur connecté sur un projet.
 *
 * - propriétaire (owner) : tous les droits, rôle exposé "ADMIN" ;
 * - membre ADMIN : modifier le projet, gérer les contributeurs, gérer les tâches ;
 * - membre CONTRIBUTOR : consulter le projet, créer / modifier / supprimer des tâches ;
 * - autre utilisateur : aucun accès (role = null).
 */
final class ProjectAccess
{
    public function __construct(
        public readonly bool $isOwner,
        public readonly ?string $role,
    ) {
    }

    public function hasAccess(): bool
    {
        return $this->role !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function canModifyProject(): bool
    {
        return $this->isAdmin();
    }

    public function canDeleteProject(): bool
    {
        return $this->isOwner;
    }

    public function canManageTasks(): bool
    {
        return $this->hasAccess();
    }
}
