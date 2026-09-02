<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Clock;
use App\Support\Id;
use PDO;

/**
 * Accès aux données des utilisateurs. Le mot de passe n'est jamais renvoyé,
 * sauf par findCredentialsByEmail / findPasswordHash (usage interne).
 */
final class UserRepository
{
    public const PUBLIC_COLUMNS = 'id, email, name';
    private const PROFILE_COLUMNS = 'id, email, name, createdAt, updatedAt';

    public function __construct(private readonly PDO $db)
    {
    }

    public function findPublicById(string $id): ?array
    {
        $statement = $this->db->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    public function findProfileById(string $id): ?array
    {
        $statement = $this->db->prepare('SELECT ' . self::PROFILE_COLUMNS . ' FROM users WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    public function findCredentialsByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT id, email, name, password, createdAt FROM users WHERE email = ?');
        $statement->execute([strtolower($email)]);

        return $statement->fetch() ?: null;
    }

    public function findPasswordHash(string $id): ?string
    {
        $statement = $this->db->prepare('SELECT password FROM users WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetchColumn() ?: null;
    }

    public function findPublicByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE email = ?');
        $statement->execute([strtolower($email)]);

        return $statement->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM users WHERE email = ?');
        $statement->execute([strtolower($email)]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * IDs des utilisateurs correspondant à une liste d'emails (les emails inconnus sont ignorés).
     * @param list<string> $emails
     * @return list<string>
     */
    public function findIdsByEmails(array $emails): array
    {
        if ($emails === []) {
            return [];
        }
        $emails = array_map('strtolower', $emails);
        $placeholders = implode(', ', array_fill(0, count($emails), '?'));
        $statement = $this->db->prepare("SELECT id FROM users WHERE email IN ($placeholders)");
        $statement->execute($emails);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function create(string $email, string $passwordHash, ?string $name): array
    {
        $now = Clock::now();
        $id = Id::generate();
        $statement = $this->db->prepare(
            'INSERT INTO users (id, email, password, name, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$id, strtolower($email), $passwordHash, $name, $now, $now]);

        return $this->findProfileById($id);
    }

    /**
     * @param array{name?: string, email?: string} $fields
     */
    public function updateProfile(string $id, array $fields): array
    {
        $assignments = [];
        $values = [];
        foreach (['name', 'email'] as $column) {
            if (array_key_exists($column, $fields)) {
                $assignments[] = "$column = ?";
                $values[] = $fields[$column];
            }
        }
        $assignments[] = 'updatedAt = ?';
        $values[] = Clock::now();
        $values[] = $id;

        $this->db->prepare('UPDATE users SET ' . implode(', ', $assignments) . ' WHERE id = ?')->execute($values);

        return $this->findProfileById($id);
    }

    public function updatePassword(string $id, string $passwordHash): void
    {
        $this->db->prepare('UPDATE users SET password = ?, updatedAt = ? WHERE id = ?')
            ->execute([$passwordHash, Clock::now(), $id]);
    }

    /**
     * Recherche par nom ou email (10 résultats maximum).
     */
    public function search(string $query): array
    {
        $statement = $this->db->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . " FROM users
             WHERE email LIKE :pattern ESCAPE '!' OR name LIKE :pattern ESCAPE '!'
             ORDER BY name, email
             LIMIT 10"
        );
        // Les caractères spéciaux de LIKE (% et _) saisis par l'utilisateur sont échappés
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
        $statement->execute(['pattern' => '%' . $escaped . '%']);

        return $statement->fetchAll();
    }
}
