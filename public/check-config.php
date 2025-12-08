<?php
/**
 * Check config/app.php providers
 */

header('Content-Type: application/json');

try {
    $laravelRoot = dirname(__DIR__);
    $configFile = $laravelRoot . '/config/app.php';
    
    if (!file_exists($configFile)) {
        echo json_encode(['error' => 'config/app.php not found']);
        exit;
    }
    
    // Load config
    $config = require $configFile;
    
    $providers = $config['providers'] ?? [];
    
    // Filter App providers
    $appProviders = array_values(array_filter($providers, function($p) {
        return is_string($p) && strpos($p, 'App\\Providers\\') === 0;
    }));
    
    $info = [
        'config_file' => $configFile,
        'config_exists' => true,
        'total_providers' => count($providers),
        'app_providers' => $appProviders,
        'route_provider_in_config' => in_array('App\\Providers\\RouteServiceProvider', $appProviders),
    ];
    
    // Check if RouteServiceProvider class exists
    require $laravelRoot . '/vendor/autoload.php';
    
    $info['route_provider_class_exists'] = class_exists('App\\Providers\\RouteServiceProvider');
    
    if ($info['route_provider_class_exists']) {
        try {
            $reflection = new ReflectionClass('App\\Providers\\RouteServiceProvider');
            $info['route_provider_file'] = $reflection->getFileName();
            $info['route_provider_parent'] = $reflection->getParentClass()->getName();
        } catch (Exception $e) {
            $info['reflection_error'] = $e->getMessage();
        }
    }
    
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}
