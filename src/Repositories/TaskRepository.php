<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Clock;
use PDO;

/**
 * Accès aux données des tâches.
 * Une tâche est renvoyée avec son créateur, son projet, ses assignations et ses commentaires.
 * Ces relations sont chargées en 3 requêtes au total, quel que soit le nombre de tâches (pas de N+1).
 */
final class TaskRepository
{
    private const SELECT_TASK = '
        SELECT t.id, t.title, t.description, t.status, t.priority, t.dueDate, t.createdAt, t.updatedAt,
               t.projectId, t.creatorId,
               c.email AS creatorEmail, c.name AS creatorName,
               p.name AS projectName
        FROM tasks t
        JOIN users c ON c.id = t.creatorId
        JOIN projects p ON p.id = t.projectId';

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Tâches assignées à un utilisateur (tous projets confondus).
     */
    public function findAssignedTo(string $userId): array
    {
        $statement = $this->db->prepare(self::SELECT_TASK . '
            WHERE EXISTS (SELECT 1 FROM task_assignees a WHERE a.taskId = t.id AND a.userId = ?)');
        $statement->execute([$userId]);

        return $this->hydrate($statement->fetchAll());
    }

    /**
     * Tâches d'un projet avec leur créateur uniquement (vue détail d'un projet).
     */
    public function findWithCreatorByProject(string $projectId): array
    {
        $statement = $this->db->prepare(self::SELECT_TASK . ' WHERE t.projectId = ? ORDER BY t.createdAt DESC');
        $statement->execute([$projectId]);

        return array_map(fn (array $row) => $this->mapTask($row, withProject: false), $statement->fetchAll());
    }

    /**
     * Tâches d'un projet, les plus urgentes en premier
     */
    public function findByProject(string $projectId): array
    {
        $statement = $this->db->prepare(self::SELECT_TASK . ' WHERE t.projectId = ? ORDER BY t.priority DESC, t.dueDate ASC');
        $statement->execute([$projectId]);

        $tasks = [];
        foreach ($statement->fetchAll() as $row) {
            $task = $this->mapTask($row, withProject: true);
            $task['assignees'] = $this->findAssignees($row['id']);
            $task['comments'] = $this->findComments($row['id']);
            $tasks[] = $task;
        }

        return $tasks;
    }

    /*
     * TODO (étudiant) : méthodes nécessaires à la création, la modification et la suppression des tâches.
     * Pensez à créer une tâche et ses assignations dans une même transaction (tout ou rien).
     */

    /**
     * Statistiques des tâches assignées à un utilisateur.
     */
    public function statsForAssignee(string $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(t.priority IN ('URGENT', 'HIGH')) AS urgent,
                SUM(t.dueDate < :now AND t.status NOT IN ('DONE', 'CANCELLED')) AS overdue,
                COUNT(DISTINCT t.projectId) AS projects
             FROM tasks t JOIN task_assignees a ON a.taskId = t.id
             WHERE a.userId = :userId"
        );
        $statement->execute(['userId' => $userId, 'now' => Clock::now()]);
        $totals = $statement->fetch();

        $byStatus = $this->db->prepare(
            'SELECT t.status, COUNT(*) AS count
             FROM tasks t JOIN task_assignees a ON a.taskId = t.id
             WHERE a.userId = ? GROUP BY t.status'
        );
        $byStatus->execute([$userId]);

        return [
            'tasks' => [
                'total' => (int) $totals['total'],
                'urgent' => (int) $totals['urgent'],
                'overdue' => (int) $totals['overdue'],
                // Cast en objet : un tableau vide doit être encodé {} et non [] en JSON
                'byStatus' => (object) array_map('intval', $byStatus->fetchAll(PDO::FETCH_KEY_PAIR)),
            ],
            'projects' => ['total' => (int) $totals['projects']],
        ];
    }

    // ---------- Hydratation ----------

    private function findAssignees(string $taskId): array
    {
        $statement = $this->db->prepare(
            'SELECT a.id, a.taskId, a.userId, a.assignedAt, u.email, u.name
             FROM task_assignees a JOIN users u ON u.id = a.userId
             WHERE a.taskId = ? ORDER BY a.assignedAt'
        );
        $statement->execute([$taskId]);

        return array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'taskId' => $row['taskId'],
            'userId' => $row['userId'],
            'assignedAt' => $row['assignedAt'],
            'user' => ['id' => $row['userId'], 'email' => $row['email'], 'name' => $row['name']],
        ], $statement->fetchAll());
    }

    private function findComments(string $taskId): array
    {
        $statement = $this->db->prepare(
            'SELECT c.id, c.content, c.createdAt, c.updatedAt, c.taskId, c.authorId, u.email, u.name
             FROM comments c JOIN users u ON u.id = c.authorId
             WHERE c.taskId = ? ORDER BY c.createdAt'
        );
        $statement->execute([$taskId]);

        return array_map([CommentRepository::class, 'mapComment'], $statement->fetchAll());
    }


    /**
     * Ajoute assignations et commentaires à une liste de tâches (2 requêtes au total).
     */
    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $taskIds = array_column($rows, 'id');
        $placeholders = Database::placeholders($taskIds);

        $assignees = $this->db->prepare(
            "SELECT a.id, a.taskId, a.userId, a.assignedAt, u.email, u.name
             FROM task_assignees a JOIN users u ON u.id = a.userId
             WHERE a.taskId IN ($placeholders) ORDER BY a.assignedAt"
        );
        $assignees->execute($taskIds);
        $assigneesByTask = [];
        foreach ($assignees->fetchAll() as $row) {
            $assigneesByTask[$row['taskId']][] = [
                'id' => $row['id'],
                'taskId' => $row['taskId'],
                'userId' => $row['userId'],
                'assignedAt' => $row['assignedAt'],
                'user' => ['id' => $row['userId'], 'email' => $row['email'], 'name' => $row['name']],
            ];
        }

        $comments = $this->db->prepare(
            "SELECT c.id, c.content, c.createdAt, c.updatedAt, c.taskId, c.authorId, u.email, u.name
             FROM comments c JOIN users u ON u.id = c.authorId
             WHERE c.taskId IN ($placeholders) ORDER BY c.createdAt"
        );
        $comments->execute($taskIds);
        $commentsByTask = [];
        foreach ($comments->fetchAll() as $row) {
            $commentsByTask[$row['taskId']][] = CommentRepository::mapComment($row);
        }

        return array_map(function (array $row) use ($assigneesByTask, $commentsByTask): array {
            $task = $this->mapTask($row, withProject: true);
            $task['assignees'] = $assigneesByTask[$row['id']] ?? [];
            $task['comments'] = $commentsByTask[$row['id']] ?? [];

            return $task;
        }, $rows);
    }

    private function mapTask(array $row, bool $withProject): array
    {
        $task = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'status' => $row['status'],
            'priority' => $row['priority'],
            'dueDate' => $row['dueDate'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt'],
            'projectId' => $row['projectId'],
            'creatorId' => $row['creatorId'],
            'creator' => ['id' => $row['creatorId'], 'email' => $row['creatorEmail'], 'name' => $row['creatorName']],
        ];
        if ($withProject) {
            $task['project'] = ['id' => $row['projectId'], 'name' => $row['projectName']];
        }

        return $task;
    }
}
