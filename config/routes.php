<?php

declare(strict_types=1);

/*
 * Table des routes de l'API.
 * Chaque route : méthode, chemin, [contrôleur, méthode], middlewares exécutés avant le contrôleur.
 */

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;
use App\Services\JwtService;

return static function (Router $router, PDO $db, array $config): void {
    $users = new UserRepository($db);
    $jwt = new JwtService($config['jwtSecret'], $config['jwtTtl']);

    $auth = new AuthMiddleware($jwt, $users);
    $protected = [$auth];

    // Authentification
    $authController = new AuthController($users, $jwt);
    $router->add('POST', '/auth/register', [$authController, 'register']);
    $router->add('POST', '/auth/login', [$authController, 'login']);
    $router->add('GET', '/auth/profile', [$authController, 'profile'], $protected);
    $router->add('PUT', '/auth/profile', [$authController, 'updateProfile'], $protected);
    $router->add('PUT', '/auth/password', [$authController, 'updatePassword'], $protected);

    // Utilisateurs
    $router->add('GET', '/users/search', [new UserController($users), 'search'], $protected);
};
