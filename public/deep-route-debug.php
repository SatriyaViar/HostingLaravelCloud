<?php
/**
 * Deep dive into route loading
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $laravelRoot = dirname(__DIR__);
    require $laravelRoot . '/vendor/autoload.php';
    
    $app = require $laravelRoot . '/bootstrap/app.php';
    
    $info = ['steps' => []];
    $info['steps'][] = 'App loaded';
    
    // Check if RouteServiceProvider is registered
    $providers = $app->getLoadedProviders();
    $info['route_provider_loaded'] = isset($providers['App\\Providers\\RouteServiceProvider']);
    $info['steps'][] = 'RouteServiceProvider loaded: ' . ($info['route_provider_loaded'] ? 'YES' : 'NO');
    
    // Try to boot the app
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $info['steps'][] = 'Kernel created';
    
    // Bootstrap without handling request
    try {
        $app->boot();
        $info['steps'][] = 'App booted';
    } catch (Throwable $e) {
        $info['boot_error'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
        ];
        $info['steps'][] = 'Boot failed: ' . $e->getMessage();
    }
    
    // Get routes after boot
    $router = $app->make('router');
    $routes = $router->getRoutes();
    $info['routes_count'] = $routes->count();
    $info['steps'][] = 'Routes count: ' . $routes->count();
    
    // List all routes
    $allRoutes = [];
    foreach ($routes as $route) {
        $allRoutes[] = [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'action' => $route->getActionName(),
        ];
    }
    $info['all_routes'] = $allRoutes;
    
    // Check if route files exist
    $info['route_files'] = [
        'api' => [
            'path' => $laravelRoot . '/routes/api.php',
            'exists' => file_exists($laravelRoot . '/routes/api.php'),
            'readable' => is_readable($laravelRoot . '/routes/api.php'),
            'size' => file_exists($laravelRoot . '/routes/api.php') ? filesize($laravelRoot . '/routes/api.php') : 0,
        ],
        'web' => [
            'path' => $laravelRoot . '/routes/web.php',
            'exists' => file_exists($laravelRoot . '/routes/web.php'),
            'readable' => is_readable($laravelRoot . '/routes/web.php'),
            'size' => file_exists($laravelRoot . '/routes/web.php') ? filesize($laravelRoot . '/routes/web.php') : 0,
        ],
    ];
    
    // Check RouteServiceProvider
    $rspFile = $laravelRoot . '/app/Providers/RouteServiceProvider.php';
    if (file_exists($rspFile)) {
        $content = file_get_contents($rspFile);
        $info['route_service_provider'] = [
            'exists' => true,
            'has_boot_method' => strpos($content, 'public function boot') !== false,
            'has_routes_call' => strpos($content, '$this->routes') !== false,
            'has_api_group' => strpos($content, "->group(base_path('routes/api.php'))") !== false,
        ];
    }
    
    echo json_encode($info, JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'FATAL_ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
    ], JSON_PRETTY_PRINT);
}
