<?php
/**
 * Check middleware status
 * Access via: https://yourdomain.com/check-middleware.php
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $laravelRoot = dirname(__DIR__);
    
    // Load composer autoload
    require $laravelRoot . '/vendor/autoload.php';
    
    // Load Laravel app
    $app = require $laravelRoot . '/bootstrap/app.php';
    
    // Get Kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Use reflection to get middleware
    $reflection = new ReflectionClass($kernel);
    
    // Get middleware groups
    $middlewareGroupsProp = $reflection->getProperty('middlewareGroups');
    $middlewareGroupsProp->setAccessible(true);
    $middlewareGroups = $middlewareGroupsProp->getValue($kernel);
    
    // Get global middleware
    $middlewareProp = $reflection->getProperty('middleware');
    $middlewareProp->setAccessible(true);
    $globalMiddleware = $middlewareProp->getValue($kernel);
    
    $info = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'kernel_class' => get_class($kernel),
        'global_middleware' => $globalMiddleware,
        'api_middleware' => $middlewareGroups['api'] ?? [],
        'web_middleware' => $middlewareGroups['web'] ?? [],
        'optimize_api_response_status' => in_array('App\\Http\\Middleware\\OptimizeApiResponse', $middlewareGroups['api'] ?? []) 
            ? 'ENABLED - This is the problem!' 
            : 'DISABLED - Should work now',
    ];
    
    echo json_encode($info, JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString()),
    ], JSON_PRETTY_PRINT);
}
