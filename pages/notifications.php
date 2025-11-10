<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");
$uid = $_SESSION['user_id'];

$res = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY id DESC LIMIT 50");
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Notifications</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="dashboard-header">
  <div class="left"><h2>🔔 Notifications</h2></div>
  <div class="right"><a href="dashboard.php" class="logout-btn">← Back</a></div>
</header>

<div class="container">
<table>
<tr><th>Title</th><th>Message</th><th>Time</th></tr>
<?php while($r=$res->fetch_assoc()): ?>
<tr class="<?=$r['is_read']?'read':'unread'?>">
  <td><?=$r['title']?></td>
  <td><?=$r['message']?></td>
  <td><?=$r['created_at']?></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
