<?php
/**
 * Router - Enkel routing för GET/POST
 *
 * Hanterar URL-routing med:
 * - Stöd för GET, POST, PUT, DELETE
 * - Dynamiska parametrar i URL:er
 * - Middleware-stöd
 * - 404-hantering
 */

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private ?string $notFoundHandler = null;
    private string $basePath = '';

    /**
     * Sätt basväg för alla routes (t.ex. '/api')
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * Registrera en GET-route
     *
     * @param string $path URL-mönster (t.ex. '/users/{id}')
     * @param callable|array $handler Funktion eller [Klass, metod]
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Registrera en POST-route
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Registrera en PUT-route
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Registrera en DELETE-route
     */
    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Registrera en route för valfri metod
     */
    public function any(string $path, callable|array $handler): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler);
        }
    }

    /**
     * Lägg till en route internt
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $fullPath = $this->basePath . $path;

        // Konvertera {param} till regex-grupp
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $this->middleware
        ];
    }

    /**
     * Lägg till middleware för kommande routes
     *
     * @param callable $middleware Funktion som körs före handler
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Rensa middleware-stacken
     */
    public function clearMiddleware(): void
    {
        $this->middleware = [];
    }

    /**
     * Sätt handler för 404 Not Found
     */
    public function notFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Kör routern och hantera inkommande request
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        // Hantera PUT/DELETE via POST med _method-fält
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Sök efter matchande route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extrahera namngivna parametrar
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Kör middleware
                foreach ($route['middleware'] as $middleware) {
                    $result = call_user_func($middleware);
                    if ($result === false) {
                        return; // Middleware avbröt
                    }
                }

                // Kör handler
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // Ingen route matchade - 404
        $this->handleNotFound();
    }

    /**
     * Hämta URI utan query string
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Ta bort query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Normalisera
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    /**
     * Anropa en handler (funktion eller klassmetod)
     */
    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            // [Klass, metod] - skapa instans och anropa
            [$class, $method] = $handler;
            $instance = new $class();
            call_user_func_array([$instance, $method], $params);
        } else {
            // Anonym funktion eller funktion
            call_user_func_array($handler, $params);
        }
    }

    /**
     * Hantera 404 Not Found
     */
    private function handleNotFound(): void
    {
        http_response_code(404);

        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
        } else {
            echo '404 - Sidan hittades inte';
        }
    }

    /**
     * Redirect till en annan URL
     */
    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Returnera JSON-svar
     */
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Hämta input från request body (för JSON API:er)
     */
    public static function getJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * Hämta GET-parameter säkert
     */
    public static function query(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8') : $default;
    }

    /**
     * Hämta POST-parameter säkert
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        return isset($_POST[$key]) ? htmlspecialchars($_POST[$key], ENT_QUOTES, 'UTF-8') : $default;
    }
}
