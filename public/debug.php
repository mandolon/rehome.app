<?php

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "Autoload OK\n";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "Bootstrap OK\n";
    
    echo "Testing routes...\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}