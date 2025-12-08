<?php
/**
 * Read raw config/app.php content
 */

header('Content-Type: text/plain');

try {
    $configFile = dirname(__DIR__) . '/config/app.php';
    
    if (!file_exists($configFile)) {
        echo "Config file not found!";
        exit;
    }
    
    $content = file_get_contents($configFile);
    
    // Find the providers section
    $start = strpos($content, "'providers'");
    if ($start === false) {
        echo "Providers section not found!";
        exit;
    }
    
    // Get 100 lines after 'providers'
    $lines = explode("\n", $content);
    $lineNum = 0;
    foreach ($lines as $i => $line) {
        if (strpos($line, "'providers'") !== false) {
            $lineNum = $i;
            break;
        }
    }
    
    echo "=== config/app.php - providers section (line $lineNum) ===\n\n";
    
    for ($i = $lineNum; $i < min($lineNum + 50, count($lines)); $i++) {
        echo sprintf("%3d: %s\n", $i + 1, $lines[$i]);
    }
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
