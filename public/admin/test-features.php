<?php
/**
 * TEST PAGE - Feature Management Debug
 * Use this to test if feature toggle works
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

AuthMiddleware::requireRole('admin');

$userId = AuthMiddleware::getUserId();
$database = new Database();
$db = $database->getConnection();

// Get community ID from URL
$communityId = isset($_GET['community_id']) ? intval($_GET['community_id']) : 1;

echo "<h1>Feature Management Test</h1>";
echo "<p>Community ID: $communityId</p>";

// Handle toggle
if (isset($_GET['toggle'])) {
    $featureId = intval($_GET['feature_id']);
    $action = $_GET['toggle'];

    echo "<hr><h3>Processing: $action Feature ID: $featureId</h3>";

    if ($action === 'enable') {
        // Manual enable
        $query = "INSERT INTO community_features (community_id, feature_id, is_enabled, enabled_by, enabled_date)
                  VALUES (:cid, :fid, 1, :uid, NOW())
                  ON DUPLICATE KEY UPDATE is_enabled = 1, enabled_by = :uid, enabled_date = NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $communityId);
        $stmt->bindParam(':fid', $featureId);
        $stmt->bindParam(':uid', $userId);

        if ($stmt->execute()) {
            echo "<p style='color:green;'>✅ Feature ENABLED successfully!</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to enable: " . print_r($stmt->errorInfo(), true) . "</p>";
        }
    } elseif ($action === 'disable') {
        // Manual disable
        $query = "UPDATE community_features
                  SET is_enabled = 0, disabled_by = :uid, disabled_date = NOW()
                  WHERE community_id = :cid AND feature_id = :fid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $communityId);
        $stmt->bindParam(':fid', $featureId);
        $stmt->bindParam(':uid', $userId);

        if ($stmt->execute()) {
            echo "<p style='color:orange;'>⚠️ Feature DISABLED successfully!</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to disable: " . print_r($stmt->errorInfo(), true) . "</p>";
        }
    }
}

echo "<hr><h2>Available Features</h2>";

// Get all features
$query = "SELECT * FROM features WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$features = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($features)) {
    echo "<p style='color:red;'>❌ No features found in database!</p>";
    echo "<p>Run this SQL:</p>";
    echo "<pre>INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order, is_active)
VALUES ('Lottery System', 'lottery_system', 'Complete lottery management', '/public/images/lottery.svg', 1, 1);</pre>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Key</th><th>Status for Community $communityId</th><th>Actions</th></tr>";

    foreach ($features as $feature) {
        // Check if enabled for this community
        $query = "SELECT is_enabled FROM community_features
                  WHERE community_id = :cid AND feature_id = :fid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $communityId);
        $stmt->bindParam(':fid', $feature['feature_id']);
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        $isEnabled = $status && $status['is_enabled'] == 1;
        $statusText = $isEnabled ? "<strong style='color:green;'>✓ ENABLED</strong>" : "<span style='color:gray;'>✗ Disabled</span>";

        echo "<tr>";
        echo "<td>{$feature['feature_id']}</td>";
        echo "<td><strong>{$feature['feature_name']}</strong></td>";
        echo "<td>{$feature['feature_key']}</td>";
        echo "<td>$statusText</td>";
        echo "<td>";

        if ($isEnabled) {
            echo "<a href='?community_id=$communityId&toggle=disable&feature_id={$feature['feature_id']}'
                     style='padding:8px 16px; background:#e74c3c; color:white; text-decoration:none; border-radius:4px;'
                     onclick='return confirm(\"Disable this feature?\")'>Disable</a>";
        } else {
            echo "<a href='?community_id=$communityId&toggle=enable&feature_id={$feature['feature_id']}'
                     style='padding:8px 16px; background:#2ecc71; color:white; text-decoration:none; border-radius:4px;'
                     onclick='return confirm(\"Enable this feature?\")'>Enable</a>";
        }

        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<hr><h2>Current Community Features Status</h2>";

$query = "SELECT f.feature_name, f.feature_key, cf.is_enabled, cf.enabled_date
          FROM features f
          LEFT JOIN community_features cf ON f.feature_id = cf.feature_id AND cf.community_id = :cid
          WHERE f.is_active = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':cid', $communityId);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr><th>Feature</th><th>Enabled?</th><th>Enabled Date</th></tr>";
foreach ($results as $row) {
    $enabled = $row['is_enabled'] ? 'YES' : 'NO';
    $date = $row['enabled_date'] ?? 'N/A';
    echo "<tr><td>{$row['feature_name']}</td><td>$enabled</td><td>$date</td></tr>";
}
echo "</table>";

echo "<hr><h2>Debug Info</h2>";
echo "<ul>";
echo "<li>User ID: $userId</li>";
echo "<li>Community ID: $communityId</li>";
echo "<li>Database Connected: " . ($db ? 'YES' : 'NO') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='/public/admin/dashboard.php'>← Back to Dashboard</a></p>";
?>
