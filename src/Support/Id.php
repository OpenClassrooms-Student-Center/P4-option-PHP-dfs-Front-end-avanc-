<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Génère des identifiants uniques non prédictibles, au format proche des CUID
 * (ex. "cm1x8k2p0000a3b4c5d6e7f8g") : on n'expose pas d'ID auto-incrémentés.
 */
final class Id
{
    public static function generate(): string
    {
        $timestamp = base_convert((string) (int) (microtime(true) * 1000), 10, 36);
        $random = '';
        while (strlen($random) < 16) {
            $random .= base_convert((string) random_int(0, PHP_INT_MAX), 10, 36);
        }

        return 'c' . str_pad($timestamp, 8, '0', STR_PAD_LEFT) . substr($random, 0, 16);
    }
}
