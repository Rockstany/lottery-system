<?php
// Simplified test version of reassign page
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: PHP is working<br>";

try {
    require_once __DIR__ . '/../../config/config.php';
    echo "Step 2: Config loaded<br>";

    AuthMiddleware::requireRole('group_admin');
    echo "Step 3: Auth passed<br>";

    $distributionId = Validator::sanitizeInt($_GET['dist_id'] ?? 0);
    echo "Step 4: Distribution ID = $distributionId<br>";

    $communityId = AuthMiddleware::getCommunityId();
    echo "Step 5: Community ID = $communityId<br>";

    if (!$distributionId || !$communityId) {
        die("Invalid parameters");
    }

    $database = new Database();
    $db = $database->getConnection();
    echo "Step 6: Database connected<br>";

    // Test the query
    $query = "SELECT bd.*, lb.*, le.event_name, le.event_id
              FROM book_distribution bd
              JOIN lottery_books lb ON bd.book_id = lb.book_id
              JOIN lottery_events le ON lb.event_id = le.event_id
              WHERE bd.distribution_id = :dist_id AND le.community_id = :community_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dist_id', $distributionId);
    $stmt->bindParam(':community_id', $communityId);
    $stmt->execute();
    $distribution = $stmt->fetch();

    if (!$distribution) {
        die("Step 7: Distribution not found");
    }

    echo "Step 7: Distribution found - Book #" . $distribution['book_number'] . "<br>";
    echo "Step 8: Event ID = " . $distribution['event_id'] . "<br>";

    echo "<br><strong>SUCCESS! All steps passed.</strong><br>";
    echo "<a href='lottery-book-reassign.php?dist_id=$distributionId'>Try the real reassign page now</a>";

} catch (Exception $e) {
    echo "<br><strong style='color:red'>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
