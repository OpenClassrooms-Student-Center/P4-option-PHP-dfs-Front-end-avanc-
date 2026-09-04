<?php

declare(strict_types=1);

namespace App\Support;

/**
 * La priorité est stockée sous forme de texte : un ORDER BY serait alphabétique
 * (HIGH, LOW, MEDIUM, URGENT). On définit donc l'ordre métier explicitement.
 */
final class TaskSorter
{
    private const PRIORITY_RANK = ['URGENT' => 0, 'HIGH' => 1, 'MEDIUM' => 2, 'LOW' => 3];

    /**
     * Trie des tâches "les plus urgentes d'abord" : priorité, puis échéance la plus proche.
     * @param list<array> $tasks
     * @return list<array>
     */
    public static function byUrgency(array $tasks): array
    {
        usort($tasks, static fn (array $a, array $b): int => [
            self::PRIORITY_RANK[$a['priority']] ?? 99,
            $a['dueDate'] ?? '9999',
        ] <=> [
            self::PRIORITY_RANK[$b['priority']] ?? 99,
            $b['dueDate'] ?? '9999',
        ]);

        return $tasks;
    }
}
