<?php

declare(strict_types=1);

/*
 * Crée (ou recrée) la base SQLite à partir de database/schema.sql.
 * Usage : composer db:init   (⚠️ supprime la base existante)
 */

[$config, $connect] = require dirname(__DIR__) . '/config/bootstrap.php';

if (is_file($config['dbPath'])) {
    unlink($config['dbPath']);
    echo "🧹 Ancienne base supprimée\n";
}

$connect()->exec((string) file_get_contents(__DIR__ . '/schema.sql'));

echo "✅ Base créée : {$config['dbPath']}\n";
