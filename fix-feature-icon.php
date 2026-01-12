<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Check current features
echo "=== CURRENT FEATURES ===\n";
$stmt = $conn->query('SELECT feature_id, feature_name, feature_icon FROM features');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['feature_id']} | Name: {$row['feature_name']} | Icon: {$row['feature_icon']}\n";
}

// Update to emoji
echo "\n=== UPDATING TO EMOJI ===\n";
$stmt = $conn->prepare('UPDATE features SET feature_icon = ? WHERE feature_key = ?');
$stmt->execute(['🎟️', 'lottery_system']);
echo "Updated lottery_system icon to 🎟️\n";

// Check again
echo "\n=== AFTER UPDATE ===\n";
$stmt = $conn->query('SELECT feature_id, feature_name, feature_icon FROM features');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['feature_id']} | Name: {$row['feature_name']} | Icon: {$row['feature_icon']}\n";
}
?>
