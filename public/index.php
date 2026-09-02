<?php

declare(strict_types=1);

/*
 * Point d'entrée unique de l'API (front controller).
 * Lancement : composer start  (équivaut à php -S localhost:8000 -t public public/index.php)
 */

use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Core\Router;
use App\Support\Clock;

[$config, $connect] = require dirname(__DIR__) . '/config/bootstrap.php';

$request = Request::fromGlobals();

// ---------- CORS : autorise uniquement les origines du front-end déclarées dans .env ----------
$origin = $request->header('Origin');
if ($origin !== null && in_array($origin, $config['corsOrigins'], true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Vary: Origin');
}
if ($request->method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- En-têtes de sécurité de base ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// ---------- Documentation de l'API ----------
if ($request->method === 'GET' && $request->path === '/api-docs') {
    header('Content-Type: text/html; charset=utf-8');
    readfile($config['root'] . '/docs/swagger.html');
    exit;
}
if ($request->method === 'GET' && $request->path === '/api-docs/openapi.yaml') {
    header('Content-Type: application/yaml; charset=utf-8');
    readfile($config['root'] . '/docs/openapi.yaml');
    exit;
}

// ---------- API ----------
try {
    if ($request->method === 'GET' && $request->path === '/health') {
        $response = new JsonResponse(200, ['success' => true, 'message' => 'API en ligne', 'timestamp' => Clock::now()]);
    } else {
        if ($config['jwtSecret'] === '') {
            throw new RuntimeException('JWT_SECRET manquant : copiez .env.example en .env et renseignez-le.');
        }
        $router = new Router();
        (require $config['root'] . '/config/routes.php')($router, $connect(), $config);
        $response = $router->dispatch($request);
    }
} catch (HttpException $exception) {
    $response = JsonResponse::error($exception->status, $exception->getMessage(), $exception->errorCode, $exception->data);
} catch (Throwable $exception) {
    // Erreur inattendue : on la journalise sans exposer de détail technique au client
    error_log('Erreur serveur : ' . $exception);
    $response = JsonResponse::error(500, 'Erreur interne du serveur', 'INTERNAL_SERVER_ERROR');
}

$response->send();
