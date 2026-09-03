<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\ProjectAccess;
use App\Support\Clock;
use App\Support\Id;
use PDO;

/**
 * Accès aux données des projets et de leurs membres.
 * Les relations (propriétaire, membres, nombre de tâches) sont chargées
 * en un nombre fixe de requêtes, quel que soit le nombre de projets.
 */
final class ProjectRepository
{
    private const SELECT_PROJECT = '
        SELECT p.id, p.name, p.description, p.createdAt, p.updatedAt, p.ownerId,
               o.email AS ownerEmail, o.name AS ownerName,
               (SELECT COUNT(*) FROM tasks t WHERE t.projectId = p.id) AS taskCount
        FROM projects p
        JOIN users o ON o.id = p.ownerId';

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Droits de l'utilisateur sur le projet, ou null si le projet n'existe pas.
     */
    public function findAccess(string $projectId, string $userId): ?ProjectAccess
    {
        $statement = $this->db->prepare(
            'SELECT p.ownerId, m.role
             FROM projects p
             LEFT JOIN project_members m ON m.projectId = p.id AND m.userId = :userId
             WHERE p.id = :projectId'
        );
        $statement->execute(['projectId' => $projectId, 'userId' => $userId]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        $isOwner = $row['ownerId'] === $userId;

        return new ProjectAccess($isOwner, $isOwner ? 'ADMIN' : $row['role']);
    }

    /**
     * Projets dont l'utilisateur est propriétaire ou membre, avec son rôle (userRole).
     */
    public function findAllForUser(string $userId): array
    {
        $statement = $this->db->prepare(self::SELECT_PROJECT . '
            WHERE p.ownerId = :userId
               OR EXISTS (SELECT 1 FROM project_members m WHERE m.projectId = p.id AND m.userId = :userId)
            ORDER BY p.updatedAt DESC');
        $statement->execute(['userId' => $userId]);
        $projects = $this->hydrate($statement->fetchAll());

        foreach ($projects as &$project) {
            $project['userRole'] = $this->resolveRole($project, $userId);
        }

        return $projects;
    }

    public function findById(string $projectId): ?array
    {
        $statement = $this->db->prepare(self::SELECT_PROJECT . ' WHERE p.id = ?');
        $statement->execute([$projectId]);

        return $this->hydrate($statement->fetchAll())[0] ?? null;
    }

    /**
     * Projets (avec leur propriétaire) correspondant à une liste d'IDs, triés par nom.
     * @param list<string> $projectIds
     */
    public function findSummariesByIds(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }
        $statement = $this->db->prepare(
            'SELECT p.id, p.name, p.description, p.createdAt, p.updatedAt, p.ownerId,
                    o.email AS ownerEmail, o.name AS ownerName
             FROM projects p JOIN users o ON o.id = p.ownerId
             WHERE p.id IN (' . Database::placeholders($projectIds) . ')
             ORDER BY p.name'
        );
        $statement->execute($projectIds);

        return array_map(fn (array $row) => $this->mapProject($row), $statement->fetchAll());
    }

    /**
     * Crée le projet et ses membres dans une transaction (tout ou rien).
     * @param list<string> $contributorIds
     */
    public function create(string $ownerId, string $name, ?string $description, array $contributorIds): array
    {
        $now = Clock::now();
        $projectId = Id::generate();

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO projects (id, name, description, ownerId, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$projectId, $name, $description, $ownerId, $now, $now]);

            foreach ($contributorIds as $userId) {
                $this->insertMember($projectId, $userId, 'CONTRIBUTOR');
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->findById($projectId);
    }

    /**
     * @param array{name?: string, description?: ?string} $fields
     */
    public function update(string $projectId, array $fields): array
    {
        $assignments = [];
        $values = [];
        foreach (['name', 'description'] as $column) {
            if (array_key_exists($column, $fields)) {
                $assignments[] = "$column = ?";
                $values[] = $fields[$column];
            }
        }
        $assignments[] = 'updatedAt = ?';
        $values[] = Clock::now();
        $values[] = $projectId;

        $this->db->prepare('UPDATE projects SET ' . implode(', ', $assignments) . ' WHERE id = ?')->execute($values);

        return $this->findById($projectId);
    }

    /**
     * Supprime le projet ; membres, tâches, assignations et commentaires suivent (ON DELETE CASCADE).
     */
    public function delete(string $projectId): void
    {
        $this->db->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
    }

    public function isOwnerOrMember(string $projectId, string $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM projects WHERE id = :projectId AND ownerId = :userId
             UNION
             SELECT 1 FROM project_members WHERE projectId = :projectId AND userId = :userId'
        );
        $statement->execute(['projectId' => $projectId, 'userId' => $userId]);

        return (bool) $statement->fetchColumn();
    }

    public function addMember(string $projectId, string $userId, string $role): array
    {
        $memberId = $this->insertMember($projectId, $userId, $role);

        return $this->findMembers([$projectId], 'm.id = ?', [$memberId])[0];
    }

    /**
     * @return int nombre de lignes supprimées (0 si l'utilisateur n'était pas membre)
     */
    public function removeMember(string $projectId, string $userId): int
    {
        $statement = $this->db->prepare('DELETE FROM project_members WHERE projectId = ? AND userId = ?');
        $statement->execute([$projectId, $userId]);

        return $statement->rowCount();
    }

    // ---------- Hydratation ----------

    private function insertMember(string $projectId, string $userId, string $role): string
    {
        $memberId = Id::generate();
        $this->db->prepare(
            'INSERT INTO project_members (id, role, joinedAt, userId, projectId) VALUES (?, ?, ?, ?, ?)'
        )->execute([$memberId, $role, Clock::now(), $userId, $projectId]);

        return $memberId;
    }

    /**
     * Transforme les lignes SQL en projets complets (propriétaire, membres, nombre de tâches).
     */
    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $projectIds = array_column($rows, 'id');
        $membersByProject = [];
        foreach ($this->findMembers($projectIds) as $member) {
            $membersByProject[$member['projectId']][] = $member;
        }

        return array_map(function (array $row) use ($membersByProject): array {
            $project = $this->mapProject($row);
            $project['members'] = $membersByProject[$row['id']] ?? [];
            $project['_count'] = ['tasks' => (int) $row['taskCount']];

            return $project;
        }, $rows);
    }

    private function mapProject(array $row): array
    {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt'],
            'ownerId' => $row['ownerId'],
            'owner' => ['id' => $row['ownerId'], 'email' => $row['ownerEmail'], 'name' => $row['ownerName']],
        ];
    }

    /**
     * Membres (avec leur utilisateur) de plusieurs projets en une seule requête.
     * @param list<string> $projectIds
     */
    private function findMembers(array $projectIds, string $extraCondition = '1 = 1', array $extraValues = []): array
    {
        $statement = $this->db->prepare(
            'SELECT m.id, m.role, m.joinedAt, m.userId, m.projectId, u.email, u.name
             FROM project_members m JOIN users u ON u.id = m.userId
             WHERE m.projectId IN (' . Database::placeholders($projectIds) . ") AND $extraCondition
             ORDER BY m.joinedAt"
        );
        $statement->execute([...$projectIds, ...$extraValues]);

        return array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'role' => $row['role'],
            'joinedAt' => $row['joinedAt'],
            'userId' => $row['userId'],
            'projectId' => $row['projectId'],
            'user' => ['id' => $row['userId'], 'email' => $row['email'], 'name' => $row['name']],
        ], $statement->fetchAll());
    }

    private function resolveRole(array $project, string $userId): ?string
    {
        if ($project['ownerId'] === $userId) {
            return 'ADMIN';
        }
        foreach ($project['members'] as $member) {
            if ($member['userId'] === $userId) {
                return $member['role'];
            }
        }

        return null;
    }
}
