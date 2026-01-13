<?php
/**
 * Diagnostic script to check csf_payments table structure
 */

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Get table structure
    echo "<h2>CSF Payments Table Structure</h2>";
    $stmt = $db->query("DESCRIBE csf_payments");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Get constraints
    echo "<h2>Table Constraints</h2>";
    $stmt = $db->query("SHOW CREATE TABLE csf_payments");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
