<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Accès aux données des commentaires (toujours renvoyés avec leur auteur).
 */
final class CommentRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /*
     * TODO (étudiant) : méthodes de lecture, création, modification et suppression des commentaires.
     * Un commentaire n'est accessible que via sa tâche, elle-même rattachée au projet de l'URL.
     */

    /**
     * Format JSON d'un commentaire, à partir d'une ligne SQL jointe avec l'auteur (colonnes email et name).
     */
    public static function mapComment(array $row): array
    {
        return [
            'id' => $row['id'],
            'content' => $row['content'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt'],
            'taskId' => $row['taskId'],
            'authorId' => $row['authorId'],
            'author' => ['id' => $row['authorId'], 'email' => $row['email'], 'name' => $row['name']],
        ];
    }
}
