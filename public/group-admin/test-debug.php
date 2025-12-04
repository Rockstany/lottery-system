<?php
// Simple test file to check PHP is working
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "Config loaded successfully!<br>";

    $database = new Database();
    $db = $database->getConnection();
    echo "Database connected successfully!<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
