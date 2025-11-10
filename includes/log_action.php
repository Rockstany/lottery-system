<?php
function log_action($conn, $user_id, $action, $details='') {
  $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $user_id, $action, $details);
  $stmt->execute();
}
?>
