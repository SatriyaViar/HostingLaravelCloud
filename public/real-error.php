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
    try {
        $laravelRoot = dirname(__DIR__);
        
        // Load composer
        require $laravelRoot . '/vendor/autoload.php';
        
        // Load Laravel
        $app = require $laravelRoot . '/bootstrap/app.php';
        
        // Create request
        $request = Illuminate\Http\Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        // Get kernel
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        
        // Handle request and capture response
        $response = $kernel->handle($request);
        
        return [
            'status' => 'success',
            'response_status' => $response->getStatusCode(),
            'response_content' => $response->getContent(),
            'response_headers' => $response->headers->all(),
        ];
        
    } catch (Throwable $e) {
        return [
            'status' => 'ERROR_CAUGHT',
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 20),
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
