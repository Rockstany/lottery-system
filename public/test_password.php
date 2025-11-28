<?php
/**
 * Test Password Hash - DELETE AFTER USE
 */

$password = 'admin123';
$hash_from_db = '$2y$10$CZ3qGSfZLNqOd.ZqkqP7V.7p8N5.5a5LR0wKYfOKvqXqQX0rqZ8Li';

echo "<h2>Password Hash Test</h2>";
echo "<p><strong>Password:</strong> {$password}</p>";
echo "<p><strong>Hash in DB:</strong> {$hash_from_db}</p>";
echo "<p><strong>Verification Result:</strong> ";

if (password_verify($password, $hash_from_db)) {
    echo "<span style='color: green; font-weight: bold;'>✓ MATCH - Password is correct!</span>";
} else {
    echo "<span style='color: red; font-weight: bold;'>✗ NO MATCH - Hash is wrong!</span>";
}

echo "</p>";
echo "<hr>";
echo "<h3>Generate New Hash:</h3>";
$new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
echo "<p><strong>New Hash:</strong> {$new_hash}</p>";
echo "<p>Copy this hash and update your database:</p>";
echo "<pre>UPDATE users SET password_hash = '{$new_hash}' WHERE mobile_number = '9999999999';</pre>";
?>
