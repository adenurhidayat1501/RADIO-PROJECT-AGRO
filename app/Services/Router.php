<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Response;

class Router
{
    private static array $routes = [];

    public static function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    public static function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    public static function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public static function dispatch(string $uri, string $method): void
    {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        $requestPath = '/' . trim($parsedUri, '/');
        $requestMethod = strtoupper($method);

        foreach (self::$routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convert route pattern {param} to regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $requestPath, $matches)) {
                // Extract named parameter arguments
                $params = array_filter($matches, '\is_string', ARRAY_FILTER_USE_KEY);

                // Run route middleware pipeline
                foreach ($route['middlewares'] as $mw) {
                    if (is_callable($mw)) {
                        call_user_func($mw);
                    } elseif (is_string($mw) && class_exists($mw)) {
                        $mw::handle();
                    }
                }

                // Execute handler
                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $params);
                    return;
                }

                if (is_array($route['handler']) && count($route['handler']) === 2) {
                    [$controllerClass, $methodName] = $route['handler'];
                    $controller = new $controllerClass();
                    call_user_func_array([$controller, $methodName], $params);
                    return;
                }
            }
        }

        // No route matched
        if (str_starts_with($requestPath, '/api/')) {
            Response::json(['error' => 'Endpoint Not Found', 'status' => 404], 404);
        } else {
            Response::abort(404, "Page '{$requestPath}' not found.");
        }
    }
}
