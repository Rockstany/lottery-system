<?php
session_start();
if(!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard | Lottery System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
  <h2>🎟️ Lottery Dashboard</h2>
  <p>Welcome, <?php echo $_SESSION['user_name']; ?>!</p>
</header>
<main>
  <div class="card">
    <p>All lotteries will appear here.</p>
  </div>
</main>
<!-- Add button bottom-right -->
<a href="create_lottery.php" class="add-button">+</a>

<div class="lottery-list">
<?php
include("../includes/db.php");
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT * FROM lotteries WHERE user_id=$user_id ORDER BY id DESC");
while($row = $res->fetch_assoc()) {
  echo "
  <div class='lottery-card'>
     <h3>{$row['name']}</h3>
     <p>ID: {$row['lottery_id']}</p>
     <p>Created: {$row['created_at']}</p>
     <p>Stage: {$row['status_stage']}</p>
     <a href='stage2_distribution.php?id={$row['id']}' class='open-btn'>Open</a>
  </div>";
}

$link = 'stage2_distribution.php';
if($row['status_stage']==3) $link = 'stage3_distribution_assign.php';
echo "<a href='$link?id={$row['id']}' class='open-btn'>Open</a>";

?>
</div>
<footer>
  <p>&copy; <?php echo date("Y"); ?> Lottery System</p>
</footer>
</body>
</html>
