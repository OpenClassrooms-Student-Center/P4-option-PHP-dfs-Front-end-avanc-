<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Création de la connexion PDO (SQLite).
 */
final class Database
{
    public static function connect(string $path): PDO
    {
        $pdo = new PDO('sqlite:' . $path, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // SQLite n'applique les clés étrangères (et donc les suppressions en cascade) que si on l'active
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Génère la liste de placeholders "?, ?, ?" pour une clause IN.
     */
    public static function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
}
