<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Clock;

/**
 * Validation des données reçues par l'API.
 * Chaque méthode renvoie une liste d'erreurs [field, message] (vide si tout est valide).
 */
final class Validator
{
    public const ROLES = ['ADMIN', 'CONTRIBUTOR'];
    public const TASK_STATUSES = ['TODO', 'IN_PROGRESS', 'DONE', 'CANCELLED'];
    public const TASK_PRIORITIES = ['LOW', 'MEDIUM', 'HIGH', 'URGENT'];

    private const PASSWORD_RULE_MESSAGE = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&)';

    public static function isValidEmail(mixed $email): bool
    {
        return is_string($email) && preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email) === 1;
    }

    public static function isValidPassword(mixed $password): bool
    {
        return is_string($password)
            && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password) === 1;
    }

    // ---------- Authentification ----------

    public static function register(array $data): array
    {
        $errors = [];
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email) {
            $errors[] = self::error('email', "L'email est requis");
        } elseif (!self::isValidEmail($email)) {
            $errors[] = self::error('email', "Format d'email invalide");
        }

        if (!$password) {
            $errors[] = self::error('password', 'Le mot de passe est requis');
        } elseif (!self::isValidPassword($password)) {
            $errors[] = self::error('password', self::PASSWORD_RULE_MESSAGE);
        }

        self::checkText($errors, 'name', $data['name'] ?? null, 'Le nom', required: false, min: 2, max: 100);

        return $errors;
    }

    public static function login(array $data): array
    {
        $errors = [];
        if (empty($data['email'])) {
            $errors[] = self::error('email', "L'email est requis");
        }
        if (empty($data['password'])) {
            $errors[] = self::error('password', 'Le mot de passe est requis');
        }

        return $errors;
    }

    public static function updateProfile(array $data): array
    {
        $errors = [];
        if (array_key_exists('email', $data) && !self::isValidEmail($data['email'])) {
            $errors[] = self::error('email', "Format d'email invalide");
        }
        if (array_key_exists('name', $data)) {
            self::checkText($errors, 'name', $data['name'], 'Le nom', required: true, min: 2, max: 100);
        }

        return $errors;
    }

    public static function updatePassword(array $data): array
    {
        $errors = [];
        $current = $data['currentPassword'] ?? null;
        $new = $data['newPassword'] ?? null;

        if (!$current) {
            $errors[] = self::error('currentPassword', 'Le mot de passe actuel est requis');
        }
        if (!$new) {
            $errors[] = self::error('newPassword', 'Le nouveau mot de passe est requis');
        } elseif (!self::isValidPassword($new)) {
            $errors[] = self::error('newPassword', self::PASSWORD_RULE_MESSAGE);
        } elseif ($current === $new) {
            $errors[] = self::error('newPassword', "Le nouveau mot de passe doit être différent de l'actuel");
        }

        return $errors;
    }

    // ---------- Projets ----------

    public static function createProject(array $data): array
    {
        $errors = [];
        self::checkText($errors, 'name', $data['name'] ?? null, 'Le nom du projet', required: true, min: 2, max: 100);
        self::checkText($errors, 'description', $data['description'] ?? null, 'La description', required: false, max: 500);

        if (array_key_exists('contributors', $data)) {
            if (!is_array($data['contributors']) || !array_is_list($data['contributors'])) {
                $errors[] = self::error('contributors', "Les contributeurs doivent être un tableau d'emails");
            } else {
                foreach ($data['contributors'] as $index => $email) {
                    if (!self::isValidEmail($email)) {
                        $errors[] = self::error("contributors[$index]", "Format d'email invalide");
                    }
                }
            }
        }

        return $errors;
    }

    public static function updateProject(array $data): array
    {
        $errors = [];
        if (array_key_exists('name', $data)) {
            self::checkText($errors, 'name', $data['name'], 'Le nom du projet', required: true, min: 2, max: 100);
        }
        self::checkText($errors, 'description', $data['description'] ?? null, 'La description', required: false, max: 500);

        return $errors;
    }

    public static function contributor(array $data): array
    {
        $errors = [];
        if (!self::isValidEmail($data['email'] ?? null)) {
            $errors[] = self::error('email', 'Un email valide est requis');
        }
        self::checkEnum($errors, $data, 'role', self::ROLES, 'Le rôle');

        return $errors;
    }

    // ---------- Tâches ----------

    public static function createTask(array $data): array
    {
        $errors = [];
        self::checkText($errors, 'title', $data['title'] ?? null, 'Le titre de la tâche', required: true, min: 2, max: 200);
        self::checkTaskFields($errors, $data);

        return $errors;
    }

    public static function updateTask(array $data): array
    {
        $errors = [];
        if (array_key_exists('title', $data)) {
            self::checkText($errors, 'title', $data['title'], 'Le titre de la tâche', required: true, min: 2, max: 200);
        }
        self::checkTaskFields($errors, $data);

        return $errors;
    }

    // ---------- Commentaires ----------

    public static function comment(array $data): array
    {
        $errors = [];
        self::checkText($errors, 'content', $data['content'] ?? null, 'Le contenu du commentaire', required: true, min: 1, max: 2000);

        return $errors;
    }

    // ---------- Helpers ----------

    private static function checkTaskFields(array &$errors, array $data): void
    {
        self::checkText($errors, 'description', $data['description'] ?? null, 'La description', required: false, max: 1000);
        self::checkEnum($errors, $data, 'status', self::TASK_STATUSES, 'Le statut');
        self::checkEnum($errors, $data, 'priority', self::TASK_PRIORITIES, 'La priorité');

        $dueDate = $data['dueDate'] ?? null;
        if ($dueDate !== null && (!is_string($dueDate) || Clock::normalize($dueDate) === null)) {
            $errors[] = self::error('dueDate', 'Format de date invalide (utilisez le format ISO 8601)');
        }

        if (array_key_exists('assigneeIds', $data)) {
            $ids = $data['assigneeIds'];
            if (!is_array($ids) || !array_is_list($ids)) {
                $errors[] = self::error('assigneeIds', "Les assignations doivent être un tableau d'IDs");
            } else {
                foreach ($ids as $index => $id) {
                    if (!is_string($id) || trim($id) === '') {
                        $errors[] = self::error("assigneeIds[$index]", "L'ID de l'utilisateur assigné est invalide");
                    }
                }
            }
        }
    }

    private static function checkText(array &$errors, string $field, mixed $value, string $label, bool $required, int $min = 0, int $max = 0): void
    {
        if ($value === null || $value === '') {
            if ($required) {
                $errors[] = self::error($field, "$label est requis");
            }
            return;
        }
        if (!is_string($value)) {
            $errors[] = self::error($field, "$label doit être une chaîne de caractères");
            return;
        }

        $length = mb_strlen(trim($value));
        if ($min > 0 && $length < $min) {
            $errors[] = self::error($field, "$label doit contenir au moins $min caractères");
        } elseif ($max > 0 && $length > $max) {
            $errors[] = self::error($field, "$label ne peut pas dépasser $max caractères");
        }
    }

    private static function checkEnum(array &$errors, array $data, string $field, array $allowed, string $label): void
    {
        if (array_key_exists($field, $data) && !in_array($data[$field], $allowed, true)) {
            $errors[] = self::error($field, "$label doit être " . implode(', ', $allowed));
        }
    }

    private static function error(string $field, string $message): array
    {
        return ['field' => $field, 'message' => $message];
    }
}
