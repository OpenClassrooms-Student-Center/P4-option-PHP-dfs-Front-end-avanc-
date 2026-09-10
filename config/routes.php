<?php

declare(strict_types=1);

/*
 * Table des routes de l'API.
 * Chaque route : méthode, chemin, [contrôleur, méthode], middlewares exécutés avant le contrôleur.
 */

use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\DashboardController;
use App\Controllers\ProjectController;
use App\Controllers\TaskController;
use App\Controllers\UserController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\ProjectAccessMiddleware;
use App\Repositories\CommentRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\JwtService;

return static function (Router $router, PDO $db, array $config): void {
    $users = new UserRepository($db);
    $projects = new ProjectRepository($db);
    $tasks = new TaskRepository($db);
    $comments = new CommentRepository($db);
    $jwt = new JwtService($config['jwtSecret'], $config['jwtTtl']);

    $auth = new AuthMiddleware($jwt, $users);
    $member = new ProjectAccessMiddleware($projects);
    $protected = [$auth];
    $projectMember = [$auth, $member];

    // Authentification
    $authController = new AuthController($users, $jwt);
    $router->add('POST', '/auth/register', [$authController, 'register']);
    $router->add('POST', '/auth/login', [$authController, 'login']);
    $router->add('GET', '/auth/profile', [$authController, 'profile'], $protected);
    $router->add('PUT', '/auth/profile', [$authController, 'updateProfile'], $protected);
    $router->add('PUT', '/auth/password', [$authController, 'updatePassword'], $protected);

    // Utilisateurs
    $router->add('GET', '/users/search', [new UserController($users), 'search'], $protected);

    // Projets et contributeurs
    $projectController = new ProjectController($projects, $users, $tasks);
    $router->add('GET', '/projects', [$projectController, 'index'], $protected);
    $router->add('POST', '/projects', [$projectController, 'store'], $protected);
    $router->add('GET', '/projects/{id}', [$projectController, 'show'], $projectMember);
    $router->add('PUT', '/projects/{id}', [$projectController, 'update'], $projectMember);
    $router->add('DELETE', '/projects/{id}', [$projectController, 'destroy'], $projectMember);
    $router->add('POST', '/projects/{id}/contributors', [$projectController, 'addContributor'], $projectMember);
    $router->add('DELETE', '/projects/{id}/contributors/{userId}', [$projectController, 'removeContributor'], $projectMember);

    // Tâches (TODO étudiant : implémenter les méthodes de TaskController)
    $taskController = new TaskController($tasks, $projects);
    $router->add('GET', '/projects/{id}/tasks', [$taskController, 'index'], $projectMember);
    $router->add('POST', '/projects/{id}/tasks', [$taskController, 'store'], $projectMember);
    $router->add('PUT', '/projects/{id}/tasks/{taskId}', [$taskController, 'update'], $projectMember);
    $router->add('DELETE', '/projects/{id}/tasks/{taskId}', [$taskController, 'destroy'], $projectMember);

    // Commentaires (TODO étudiant : implémenter les méthodes de CommentController)
    $commentController = new CommentController($comments, $tasks);
    $base = '/projects/{id}/tasks/{taskId}/comments';
    $router->add('GET', $base, [$commentController, 'index'], $projectMember);
    $router->add('POST', $base, [$commentController, 'store'], $projectMember);
    $router->add('GET', "$base/{commentId}", [$commentController, 'show'], $projectMember);
    $router->add('PUT', "$base/{commentId}", [$commentController, 'update'], $projectMember);
    $router->add('DELETE', "$base/{commentId}", [$commentController, 'destroy'], $projectMember);

    // Tableau de bord
    $dashboardController = new DashboardController($tasks, $projects);
    $router->add('GET', '/dashboard/assigned-tasks', [$dashboardController, 'assignedTasks'], $protected);
    $router->add('GET', '/dashboard/projects-with-tasks', [$dashboardController, 'projectsWithTasks'], $protected);
    $router->add('GET', '/dashboard/stats', [$dashboardController, 'stats'], $protected);
};
