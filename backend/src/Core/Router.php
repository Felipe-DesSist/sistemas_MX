<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            Response::json(['error' => 'Not found'], 404);
            return;
        }

        [$class, $action] = $handler;
        (new $class())->$action($request);
    }
}
