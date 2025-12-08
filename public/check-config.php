<?php
/**
 * Check config/app.php providers
 */

header('Content-Type: application/json');

try {
    $laravelRoot = dirname(__DIR__);
    
    // Load composer and Laravel
    require $laravelRoot . '/vendor/autoload.php';
    $app = require $laravelRoot . '/bootstrap/app.php';
    
    // Get config WITHOUT booting (to avoid provider loading issues)
    $configFile = $laravelRoot . '/config/app.php';
    
    if (!file_exists($configFile)) {
        echo json_encode(['error' => 'config/app.php not found']);
        exit;
    }
    
    // Read file content instead
    $configContent = file_get_contents($configFile);
    
    // Extract providers array using regex
    preg_match("/['\"](providers)['\"]\\s*=>\\s*\\[(.*?)\\]/s", $configContent, $matches);
    
    $providersString = $matches[2] ?? '';
    preg_match_all("/App\\\\Providers\\\\(\\w+)::class/", $providersString, $providerMatches);
    
    $appProviders = array_map(function($name) {
        return "App\\Providers\\$name";
    }, $providerMatches[1] ?? []);
    
    $config = null;
    
    $info = [
        'config_file' => $configFile,
        'config_exists' => true,
        'app_providers_found' => $appProviders,
        'route_provider_in_config' => in_array('App\\Providers\\RouteServiceProvider', $appProviders),
    ];
    
    // Check if RouteServiceProvider class exists
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
