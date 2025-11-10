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
<header class="dashboard-header">
  <div class="left">
    <h2>🎟️ Lottery Dashboard</h2>
  </div>
  <div class="right">
    <p>Welcome, <?php echo $_SESSION['user_name']; ?>!</p>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>
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

  // 🧠 Dynamic link logic (Decides which stage page to open)
  $link = 'stage2_distribution.php';
  if($row['status_stage'] == 3) $link = 'stage3_distribution_assign.php';
  if($row['status_stage'] == 4) $link = 'stage4_collection.php';
  if($row['status_stage'] == 5) $link = 'stage5_summary.php';

  $progress = ($row['status_stage'] / 5) * 100;
echo "
  <div class='lottery-card'>
     <h3>{$row['name']}</h3>
     <p>ID: {$row['lottery_id']}</p>
     <p>Created: {$row['created_at']}</p>
     <div class='progress-bar'>
       <div class='progress' style='width: {$progress}%;'></div>
     </div>
     <p class='stage-label'>Stage {$row['status_stage']} of 5</p>
     <a href='$link?id={$row['id']}' class='open-btn'>Open</a>
  </div>";

}
?>
</div>

<footer>
  <p>&copy; <?php echo date('Y'); ?> Lottery System</p>
</footer>
</body>
</html>
