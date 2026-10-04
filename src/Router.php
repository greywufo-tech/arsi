<?php
declare(strict_types=1);

/**
 * Router sederhana dengan dukungan parameter path, mis. /pesanan/{id}.
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, keys:string[], handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $keys = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            function (array $m) use (&$keys) {
                $keys[] = $m[1];
                return '(\d+)';
            },
            $pattern
        );

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'keys'    => $keys,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        if ($path === '') {
            $path = '/';
        }

        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['keys'], $matches) ?: [];

            call_user_func($route['handler'], $params);
            return;
        }

        if ($pathMatched) {
            http_response_code(405);
            view('errors/405');
            return;
        }

        http_response_code(404);
        view('errors/404');
    }
}