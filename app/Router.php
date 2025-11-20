<?php
/**
 * Simple Router for API endpoints
 */

class Router {
    private $routes = [];
    private $request;
    private $response;

    public function __construct() {
        $this->request = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'query' => $_GET
        ];

        // Remove base path from request path
        $basePath = '/imanage/public';
        if (strpos($this->request['path'], $basePath) === 0) {
            $this->request['path'] = substr($this->request['path'], strlen($basePath));
        }
    }

    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }

    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }

    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function dispatch() {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->request['method']) {
                continue;
            }

            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $route['path']);
            $pattern = '@^' . $pattern . '$@';

            if (preg_match($pattern, $this->request['path'], $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }

                call_user_func_array($route['callback'], array_values($params));
                return;
            }
        }

        // Route not found
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Route not found'
        ]);
        exit;
    }
}
