<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Routeur minimaliste : associe "MÉTHODE /chemin/{param}" à un contrôleur,
 * précédé d'une liste de middlewares.
 */
class Router
{
    /** @var list<array{method: string, regex: string, handler: callable, middlewares: list<callable>}> */
    private array $routes = [];

    /**
     * @param callable(Request): JsonResponse $handler
     * @param list<callable(Request): void> $middlewares exécutés dans l'ordre avant le contrôleur
     */
    public function add(string $method, string $pattern, callable $handler, array $middlewares = []): void
    {
        // "/projects/{id}" devient "#^/projects/(?P<id>[^/]+)$#"
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): JsonResponse
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method || !preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }

            $request->params = array_map('rawurldecode', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            foreach ($route['middlewares'] as $middleware) {
                $middleware($request);
            }

            return ($route['handler'])($request);
        }

        throw HttpException::notFound('Route non trouvée');
    }
}
