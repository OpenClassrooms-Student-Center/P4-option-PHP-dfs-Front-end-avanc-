<?php

declare(strict_types=1);

/*
 * Table des routes de l'API.
 * Chaque route : méthode, chemin, [contrôleur, méthode], middlewares exécutés avant le contrôleur.
 */

use App\Controllers\AuthController;
use App\Controllers\ProjectController;
use App\Controllers\UserController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\ProjectAccessMiddleware;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\JwtService;

return static function (Router $router, PDO $db, array $config): void {
    $users = new UserRepository($db);
    $projects = new ProjectRepository($db);
    $tasks = new TaskRepository($db);
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
};
