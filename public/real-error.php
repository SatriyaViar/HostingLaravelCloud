<?php
/**
 * Ultra diagnostic - shows real error with full details
 * Access via: https://yourdomain.com/real-error.php
 */

// Force display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

function captureError() {
    $steps = [];
    
    try {
        $laravelRoot = dirname(__DIR__);
        $steps[] = 'Starting';
        
        // Load composer
        require $laravelRoot . '/vendor/autoload.php';
        $steps[] = 'Composer loaded';
        
        // Load Laravel
        $app = require $laravelRoot . '/bootstrap/app.php';
        $steps[] = 'Laravel app loaded';
        
        // Check if routes are loaded
        $router = $app->make('router');
        $routes = $router->getRoutes();
        $steps[] = 'Routes count: ' . $routes->count();
        
        // Find api/test route
        $apiTestRoute = $routes->getByName('api.test') ?? $routes->getByAction('api/test');
        $steps[] = 'API test route: ' . ($apiTestRoute ? 'found' : 'NOT FOUND');
        
        // List all API routes
        $apiRoutes = [];
        foreach ($routes as $route) {
            if (strpos($route->uri(), 'api/') === 0) {
                $apiRoutes[] = $route->uri() . ' [' . implode(',', $route->methods()) . ']';
            }
        }
        $steps[] = 'API routes found: ' . count($apiRoutes);
        
        // Try to get the route
        try {
            $request = Illuminate\Http\Request::create('/api/test', 'GET');
            $request->headers->set('Accept', 'application/json');
            $steps[] = 'Request created';
            
            // Get kernel
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $steps[] = 'Kernel created';
            
            // Bootstrap kernel
            $kernel->bootstrap();
            $steps[] = 'Kernel bootstrapped';
            
            // Handle request
            $response = $kernel->handle($request);
            $steps[] = 'Response received: ' . $response->getStatusCode();
            
            return [
                'status' => 'success',
                'steps' => $steps,
                'response_status' => $response->getStatusCode(),
                'response_content' => $response->getContent(),
                'api_routes' => array_slice($apiRoutes, 0, 10),
            ];
            
        } catch (Throwable $e) {
            $steps[] = 'Error during request handling';
            throw $e;
        }
        
    } catch (Throwable $e) {
        return [
            'status' => 'ERROR_CAUGHT',
            'steps' => $steps,
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
            'previous' => $e->getPrevious() ? [
                'message' => $e->getPrevious()->getMessage(),
                'file' => $e->getPrevious()->getFile(),
                'line' => $e->getPrevious()->getLine(),
            ] : null,
        ];
    }
}

// Capture output
ob_start();
$result = captureError();
$output = ob_get_clean();

if ($output) {
    $result['captured_output'] = $output;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
