<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Les dates sont stockées et renvoyées au format ISO 8601 UTC avec millisecondes
 * (ex. "2026-01-15T00:00:00.000Z"), comme la variante JavaScript.
 */
final class Clock
{
    private const FORMAT = 'Y-m-d\TH:i:s.v\Z';

    public static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(self::FORMAT);
    }

    /**
     * Normalise une date reçue du client ; renvoie null si elle est invalide.
     */
    public static function normalize(string $value): ?string
    {
        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }

        return $date->setTimezone(new DateTimeZone('UTC'))->format(self::FORMAT);
    }

    public static function daysFromNow(int $days): string
    {
        return (new DateTimeImmutable('today', new DateTimeZone('UTC')))
            ->modify(sprintf('%+d days', $days))
            ->format(self::FORMAT);
    }
}
