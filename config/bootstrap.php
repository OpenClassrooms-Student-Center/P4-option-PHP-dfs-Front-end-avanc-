<?php

declare(strict_types=1);

/*
 * Chargement de la configuration et création des services (injection de dépendances "à la main").
 * Utilisé par public/index.php et par les scripts du dossier database/.
 */

use App\Core\Database;
use Dotenv\Dotenv;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

Dotenv::createImmutable($root)->safeLoad();

$config = [
    'root' => $root,
    'dbPath' => $root . '/' . ($_ENV['DB_PATH'] ?? 'database/abricot.sqlite'),
    'jwtSecret' => $_ENV['JWT_SECRET'] ?? '',
    'jwtTtl' => (int) ($_ENV['JWT_TTL'] ?? 604800),
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'corsOrigins' => array_filter(array_map('trim', explode(',', $_ENV['CORS_ORIGINS'] ?? ''))),
];

return [$config, static fn (): PDO => Database::connect($config['dbPath'])];
