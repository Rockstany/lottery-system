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

<!-- Search + Filter -->
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

<?php
include("../includes/db.php");
$user_id = $_SESSION['user_id'];
$res = $conn->query("SELECT * FROM lotteries WHERE user_id=$user_id ORDER BY id DESC");

// --- Filters ---
$q      = trim($_GET['q'] ?? '');
$stage  = $_GET['stage'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE user_id=$user_id";
if($q)     $where .= " AND (name LIKE '%$q%' OR lottery_id LIKE '%$q%')";
if($stage) $where .= " AND status_stage=$stage";
if($from)  $where .= " AND DATE(created_at)>='$from'";
if($to)    $where .= " AND DATE(created_at)<='$to'";

$total = $conn->query("SELECT COUNT(*) AS c FROM lotteries $where")->fetch_assoc()['c'];
$pages = ceil($total / $limit);

$res = $conn->query("SELECT * FROM lotteries $where ORDER BY id DESC LIMIT $limit OFFSET $offset");

while($row = $res->fetch_assoc()) {
  $link = 'stage2_distribution.php';
  if($row['status_stage']==3) $link='stage3_distribution_assign.php';
  if($row['status_stage']==4) $link='stage4_collection.php';
  if($row['status_stage']==5) $link='stage5_summary.php';
  $progress = ($row['status_stage']/5)*100;

  echo "
  <div class='lottery-card'>
    <h3>{$row['name']}</h3>
    <p>ID: {$row['lottery_id']}</p>
    <p>Created: {$row['created_at']}</p>
    <div class='progress-bar'><div class='progress' style='width:{$progress}%;'></div></div>
    <p class='stage-label'>Stage {$row['status_stage']} of 5</p>
    <a href='$link?id={$row['id']}' class='open-btn'>Open</a>
  </div>";
}

// --- Pagination ---
echo "<div class='pagination'>";
for($p=1;$p<=$pages;$p++){
  $active = ($p==$page)?'active':'';
  $url = '?'.http_build_query(array_merge($_GET,['page'=>$p]));
  echo "<a href='$url' class='page-btn $active'>$p</a>";
}
echo "</div>";

?>
</div>

<footer>
  <p>&copy; <?php echo date('Y'); ?> Lottery System</p>
</footer>
</body>
</html>
