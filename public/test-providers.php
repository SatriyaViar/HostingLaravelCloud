<?php
/**
 * Check all providers and their loading status
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    $laravelRoot = dirname(__DIR__);
    require $laravelRoot . '/vendor/autoload.php';
    
    $info = ['steps' => []];
    
    // Create app
    $app = require $laravelRoot . '/bootstrap/app.php';
    $info['steps'][] = 'App created';
    
    // Get configured providers from config
    $configProviders = require $laravelRoot . '/config/app.php';
    $providersConfig = $configProviders['providers'] ?? [];
    
    $info['configured_providers'] = array_values(array_filter($providersConfig, function($p) {
        return strpos($p, 'App\\') === 0;
    }));
    
    $info['steps'][] = 'Found ' . count($info['configured_providers']) . ' App providers in config';
    
    // Try to register each provider manually and catch errors
    $providerTests = [];
    foreach ($info['configured_providers'] as $providerClass) {
        try {
            if (!class_exists($providerClass)) {
                $providerTests[$providerClass] = [
                    'status' => 'CLASS_NOT_FOUND',
                    'exists' => false,
                ];
                continue;
            }
            
            // Try to instantiate
            $provider = new $providerClass($app);
            $providerTests[$providerClass] = [
                'status' => 'OK',
                'instantiated' => true,
            ];
            
            // Try to call register method
            if (method_exists($provider, 'register')) {
                $provider->register();
                $providerTests[$providerClass]['register'] = 'OK';
            }
            
        } catch (Throwable $e) {
            $providerTests[$providerClass] = [
                'status' => 'ERROR',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
            ];
        }
    }
    
    $info['provider_tests'] = $providerTests;
    
    // Check actual loaded providers
    $info['steps'][] = 'Testing complete';
    
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'FATAL',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
    ], JSON_PRETTY_PRINT);
}

restore_error_handler();
