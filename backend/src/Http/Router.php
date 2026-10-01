<?php

declare(strict_types=1);

namespace App\Http;

use App\Jwt;

final class Router
{
    public const PUBLIC = 'public';
    public const USER = 'user';
    public const ADMIN = 'admin';

    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, string $access = self::USER): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $pattern) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'access');
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            $allowed[] = $route['method'];
            if ($route['method'] !== $request->method) {
                continue;
            }

            if ($route['access'] !== self::PUBLIC) {
                if ($request->token === null) {
                    throw new HttpException(401, 'No autenticado.');
                }
                $request->user = Jwt::decode($request->token, Jwt::secret());
                if ($route['access'] === self::ADMIN && !$request->isAdmin()) {
                    throw new HttpException(403, 'Solo un administrador puede realizar esta acción.');
                }
            }

            $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            $result = ($route['handler'])($request, ...$params);
            return $result instanceof Response ? $result : new Response($result);
        }

        throw $allowed
            ? new HttpException(405, 'Método no permitido.')
            : new HttpException(404, 'Recurso no encontrado.');
    }
}
