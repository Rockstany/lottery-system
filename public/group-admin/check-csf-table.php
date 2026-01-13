<?php
/**
 * Diagnostic script to check csf_payments table structure
 */

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$db = $database->getConnection();

?>
<!DOCTYPE html>
<html>
<head>
    <title>CSF Payments Table Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .error { color: red; padding: 20px; background: #ffe6e6; border-radius: 5px; }
        .success { color: green; padding: 20px; background: #e6ffe6; border-radius: 5px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>CSF Payments Table Diagnostic</h1>

<?php
try {
    // Get table structure
    echo "<h2>Table Structure (DESCRIBE csf_payments)</h2>";
    $stmt = $db->query("DESCRIBE csf_payments");
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? '<em>NULL</em>') . "</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Get constraints
    echo "<h2>Complete Table Definition (SHOW CREATE TABLE)</h2>";
    $stmt = $db->query("SHOW CREATE TABLE csf_payments");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";

    // Sample payment record (if exists)
    echo "<h2>Sample Payment Record (Latest)</h2>";
    $stmt = $db->query("SELECT * FROM csf_payments ORDER BY created_at DESC LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sample) {
        echo "<table>";
        echo "<tr><th>Column</th><th>Value</th></tr>";
        foreach ($sample as $key => $value) {
            echo "<tr>";
            echo "<td><strong>$key</strong></td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>No payment records found yet.</em></p>";
    }

    echo "<div class='success'>✓ Diagnostic completed successfully!</div>";

} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

    <hr>
    <p><a href="csf-funds.php">← Back to CSF Funds</a></p>
</body>
</html>
