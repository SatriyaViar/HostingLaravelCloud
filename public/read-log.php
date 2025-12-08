<?php
/**
 * Read Laravel log file
 * Access via: https://yourdomain.com/read-log.php
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $laravelRoot = dirname(__DIR__);
    $logPath = $laravelRoot . '/storage/logs/laravel.log';
    
    if (!file_exists($logPath)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Log file not found',
            'path' => $logPath,
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Read last 100 lines
    $lines = file($logPath);
    $totalLines = count($lines);
    $lastLines = array_slice($lines, -100);
    
    // Find recent errors (last 50 lines)
    $recentErrors = [];
    $errorPattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*\.(ERROR|CRITICAL|ALERT|EMERGENCY):(.*)/';
    
    foreach (array_slice($lines, -50) as $line) {
        if (preg_match($errorPattern, $line, $matches)) {
            $recentErrors[] = [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => trim($matches[3]),
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'log_path' => $logPath,
        'total_lines' => $totalLines,
        'recent_errors' => $recentErrors,
        'last_100_lines' => implode('', $lastLines),
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}
