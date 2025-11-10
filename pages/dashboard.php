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
    <h2>🎟️ <span>Lottery Dashboard</span></h2>
  </div>
  <div class="right">
    <a href="notifications.php" class="bell">🔔</a>
    <p>Welcome, <strong><?php echo $_SESSION['user_name']; ?></strong>!</p>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<main class="dashboard-main">
  <div class="dashboard-top">
    <h3>Your Lotteries</h3>
    <a href="create_lottery.php" class="add-button">＋ New Lottery</a>
  </div>

  <!-- Filter Section -->
  <div class="filter-bar">
    <form method="GET">
      <input type="text" name="q" placeholder="Search by name or ID" value="<?=htmlspecialchars($_GET['q'] ?? '')?>">
      <select name="stage">
        <option value="">All Stages</option>
        <?php for($i=1;$i<=5;$i++): ?>
          <option value="<?=$i?>" <?=($_GET['stage']??'')==$i?'selected':''?>>Stage <?=$i?></option>
        <?php endfor; ?>
      </select>
      <input type="date" name="from" value="<?=htmlspecialchars($_GET['from'] ?? '')?>">
      <input type="date" name="to" value="<?=htmlspecialchars($_GET['to'] ?? '')?>">
      <button type="submit">Filter</button>
    </form>
  </div>

  <!-- Lottery List -->
  <div class="lottery-list">
    <?php
    while($row = $res->fetch_assoc()) {
      $link = 'stage2_distribution.php';
      if($row['status_stage']==3) $link='stage3_distribution_assign.php';
      if($row['status_stage']==4) $link='stage4_collection.php';
      if($row['status_stage']==5) $link='stage5_summary.php';
      $progress = ($row['status_stage']/5)*100;

      echo "
      <div class='lottery-card'>
        <div class='lottery-header'>
          <h4>{$row['name']}</h4>
          <span class='lottery-id'>#{$row['lottery_id']}</span>
        </div>
        <p>Created on: <strong>{$row['created_at']}</strong></p>
        <div class='progress-bar'><div class='progress' style='width:{$progress}%;'></div></div>
        <p class='stage-label'>Stage {$row['status_stage']} of 5</p>
        <div class='actions'>
          <a href='$link?id={$row['id']}' class='open-btn'>Open</a>
          <a href='duplicate_lottery.php?id={$row['id']}' class='dup-btn'>Duplicate</a>
        </div>
      </div>";
    }
    ?>
  </div>
</main>

<footer>
  <p>© <?=date('Y')?> Lottery System | Built by Albin Thomas</p>
</footer>
<script src="../assets/js/toast.js"></script>
</body>
</html>
