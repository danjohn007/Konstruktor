<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable|array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable|array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $methodName] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $methodName], $matches);
                } else {
                    call_user_func_array($handler, $matches);
                }
                return;
            }
        }

        http_response_code(404);
        if (str_starts_with($uri, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ruta no encontrada']);
        } else {
            echo 'Página no encontrada';
        }
    }
}

