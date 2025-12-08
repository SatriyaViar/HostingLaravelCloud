<?php
/**
 * Force delete ALL cache files - no Laravel bootstrap
 */

header('Content-Type: application/json');

try {
    $laravelRoot = dirname(__DIR__);
    $results = [];
    
    // List of all possible cache files
    $cacheFiles = [
        'bootstrap/cache/config.php',
        'bootstrap/cache/services.php',
        'bootstrap/cache/packages.php',
        'bootstrap/cache/routes-v7.php',
        'bootstrap/cache/routes.php',
        'bootstrap/cache/compiled.php',
        'bootstrap/cache/manifest.php',
    ];
    
    foreach ($cacheFiles as $file) {
        $fullPath = $laravelRoot . '/' . $file;
        if (file_exists($fullPath)) {
            $deleted = @unlink($fullPath);
            $results[$file] = $deleted ? 'DELETED' : 'FAILED TO DELETE';
        } else {
            $results[$file] = 'not found';
        }
    }
    
    // Clear entire bootstrap/cache directory (except .gitignore)
    $cacheDir = $laravelRoot . '/bootstrap/cache';
    if (is_dir($cacheDir)) {
        $files = scandir($cacheDir);
        $cleared = [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && $file !== '.gitignore') {
                $fullPath = $cacheDir . '/' . $file;
                if (is_file($fullPath)) {
                    $deleted = @unlink($fullPath);
                    $cleared[$file] = $deleted ? 'DELETED' : 'FAILED';
                }
            }
        }
        $results['all_cache_files'] = $cleared;
    }
    
    // Clear view cache
    $viewCacheDir = $laravelRoot . '/storage/framework/views';
    if (is_dir($viewCacheDir)) {
        $files = glob($viewCacheDir . '/*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                @unlink($file);
                $count++;
            }
        }
        $results['view_cache'] = "$count files deleted";
    }
    
    // Clear compiled cache
    $compiledDir = $laravelRoot . '/storage/framework/cache/data';
    if (is_dir($compiledDir)) {
        $files = glob($compiledDir . '/*/*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
                $count++;
            }
        }
        $results['compiled_cache'] = "$count files deleted";
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'All caches force deleted',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $results,
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
}
