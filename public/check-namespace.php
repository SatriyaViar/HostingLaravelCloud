<?php
/**
 * Check RouteServiceProvider namespace
 */

header('Content-Type: application/json');

try {
    $file = dirname(__DIR__) . '/app/Providers/RouteServiceProvider.php';
    $content = file_get_contents($file);
    
    // Extract namespace
    preg_match('/protected \$namespace = [\'"](.+?)[\'"];/', $content, $matches);
    
    $info = [
        'file_exists' => file_exists($file),
        'file_modified' => date('Y-m-d H:i:s', filemtime($file)),
        'namespace_found' => $matches[1] ?? 'NOT FOUND',
        'is_correct' => ($matches[1] ?? '') === 'App\\Http\\Controllers',
        'current_time' => date('Y-m-d H:i:s'),
    ];
    
    // Also check if there's a cached version
    $cachedConfig = dirname(__DIR__) . '/bootstrap/cache/config.php';
    if (file_exists($cachedConfig)) {
        $info['cached_config'] = [
            'exists' => true,
            'modified' => date('Y-m-d H:i:s', filemtime($cachedConfig)),
        ];
    }
    
    $cachedRoutes = dirname(__DIR__) . '/bootstrap/cache/routes-v7.php';
    if (file_exists($cachedRoutes)) {
        $info['cached_routes'] = [
            'exists' => true,
            'modified' => date('Y-m-d H:i:s', filemtime($cachedRoutes)),
        ];
    }
    
    echo json_encode($info, JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}
