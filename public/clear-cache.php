<?php
/**
 * Clear all Laravel caches
 * Access via: https://yourdomain.com/clear-cache.php
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
    
    // Boot the application
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $results = [];
    
    // Clear config cache
    if (file_exists($laravelRoot . '/bootstrap/cache/config.php')) {
        unlink($laravelRoot . '/bootstrap/cache/config.php');
        $results['config_cache'] = 'cleared';
    } else {
        $results['config_cache'] = 'not found';
    }
    
    // Clear services cache
    if (file_exists($laravelRoot . '/bootstrap/cache/services.php')) {
        unlink($laravelRoot . '/bootstrap/cache/services.php');
        $results['services_cache'] = 'cleared';
    } else {
        $results['services_cache'] = 'not found';
    }
    
    // Clear packages cache
    if (file_exists($laravelRoot . '/bootstrap/cache/packages.php')) {
        unlink($laravelRoot . '/bootstrap/cache/packages.php');
        $results['packages_cache'] = 'cleared';
    } else {
        $results['packages_cache'] = 'not found';
    }
    
    // Clear route cache
    if (file_exists($laravelRoot . '/bootstrap/cache/routes-v7.php')) {
        unlink($laravelRoot . '/bootstrap/cache/routes-v7.php');
        $results['route_cache'] = 'cleared';
    } else {
        $results['route_cache'] = 'not found';
    }
    
    // Clear view cache
    $viewCachePath = $laravelRoot . '/storage/framework/views';
    if (is_dir($viewCachePath)) {
        $files = glob($viewCachePath . '/*');
        $cleared = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
                $cleared++;
            }
        }
        $results['view_cache'] = "$cleared files cleared";
    }
    
    // Clear application cache
    $appCachePath = $laravelRoot . '/storage/framework/cache/data';
    if (is_dir($appCachePath)) {
        $files = glob($appCachePath . '/*/*');
        $cleared = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared++;
            }
        }
        $results['app_cache'] = "$cleared files cleared";
    }
    
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'All caches cleared',
        'results' => $results,
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}
