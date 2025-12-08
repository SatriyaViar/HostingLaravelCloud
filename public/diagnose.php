<?php
/**
 * Direct PHP diagnostic - bypasses Laravel completely
 * Access via: https://yourdomain.com/diagnose.php
 */

header('Content-Type: application/json');

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $info = [
        'status' => 'running',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    ];

    // Check if we can access Laravel root
    $laravelRoot = dirname(__DIR__);
    $info['paths'] = [
        'public' => __DIR__,
        'laravel_root' => $laravelRoot,
        'laravel_root_exists' => is_dir($laravelRoot),
    ];

    // Check vendor autoload
    $autoloadPath = $laravelRoot . '/vendor/autoload.php';
    $info['composer'] = [
        'autoload_path' => $autoloadPath,
        'autoload_exists' => file_exists($autoloadPath),
    ];

    // Try to load Laravel
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
        $info['composer']['autoload_loaded'] = true;

        // Try to load Laravel app
        $appPath = $laravelRoot . '/bootstrap/app.php';
        $info['laravel'] = [
            'app_path' => $appPath,
            'app_exists' => file_exists($appPath),
        ];

        if (file_exists($appPath)) {
            try {
                $app = require $appPath;
                $info['laravel']['app_loaded'] = true;
                $info['laravel']['app_class'] = get_class($app);

                // Try to boot Laravel
                $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
                $info['laravel']['kernel_loaded'] = true;
                $info['laravel']['kernel_class'] = get_class($kernel);

            } catch (Throwable $e) {
                $info['laravel']['boot_error'] = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            }
        }
    }

    // Check app folder structure
    $appFolderPath = $laravelRoot . '/app';
    $info['app_folder'] = [
        'path' => $appFolderPath,
        'exists' => is_dir($appFolderPath),
        'readable' => is_readable($appFolderPath),
    ];

    if (is_dir($appFolderPath)) {
        $info['app_folder']['contents'] = array_values(array_diff(scandir($appFolderPath), ['.', '..']));
        
        // Check specific folders
        $info['app_folder']['subfolders'] = [
            'Http' => is_dir($appFolderPath . '/Http'),
            'Models' => is_dir($appFolderPath . '/Models'),
            'Providers' => is_dir($appFolderPath . '/Providers'),
            'Exceptions' => is_dir($appFolderPath . '/Exceptions'),
        ];
    }

    // Check .env file
    $envPath = $laravelRoot . '/.env';
    $info['env_file'] = [
        'path' => $envPath,
        'exists' => file_exists($envPath),
        'readable' => is_readable($envPath),
    ];

    // Check bootstrap/cache
    $cachePath = $laravelRoot . '/bootstrap/cache';
    $info['cache'] = [
        'path' => $cachePath,
        'exists' => is_dir($cachePath),
        'writable' => is_writable($cachePath),
    ];

    if (is_dir($cachePath)) {
        $cacheFiles = array_values(array_diff(scandir($cachePath), ['.', '..', '.gitignore']));
        $info['cache']['files'] = $cacheFiles;
    }

    echo json_encode($info, JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'FATAL_ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString()),
    ], JSON_PRETTY_PRINT);
}
