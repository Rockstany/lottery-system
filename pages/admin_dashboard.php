<?php
session_start();
include("../includes/db.php");

// Only admins allowed
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");
$user_id = $_SESSION['user_id'];
$role = $conn->query("SELECT role FROM users WHERE id=$user_id")->fetch_assoc()['role'];
if($role != 'admin'){ header("Location: dashboard.php"); exit; }

// ---- Overall counts
$lotteries = $conn->query("SELECT COUNT(*) AS c FROM lotteries")->fetch_assoc()['c'];
$users = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$books = $conn->query("SELECT COUNT(*) AS c FROM booklets")->fetch_assoc()['c'];

// ---- Payments summary
$p = $conn->query("SELECT 
    SUM(amount) AS total,
    SUM(CASE WHEN b.status='paid' THEN 1 ELSE 0 END) AS full,
    SUM(CASE WHEN b.status='partial' THEN 1 ELSE 0 END) AS partial
FROM payments p 
JOIN booklets b ON b.id=p.booklet_id")->fetch_assoc();

$totalRevenue = $p['total'] ?? 0;
$fullPaid = $p['full'] ?? 0;
$partialPaid = $p['partial'] ?? 0;

// ---- Recent lotteries
$recent = $conn->query("SELECT name, lottery_id, created_at, status_stage FROM lotteries ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Lottery System</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="dashboard-header">
  <div class="left"><h2>🎯 Admin Dashboard</h2></div>
  <div class="right">
    <p>Welcome, Admin</p>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <h3>System Overview</h3>
  <div class="admin-stats">
    <div class="stat-card"><h4>Total Users</h4><p><?=$users?></p></div>
    <div class="stat-card"><h4>Total Lotteries</h4><p><?=$lotteries?></p></div>
    <div class="stat-card"><h4>Total Booklets</h4><p><?=$books?></p></div>
    <div class="stat-card"><h4>Total Revenue</h4><p>₹<?=$totalRevenue?></p></div>
  </div>

  <hr>
  <h3>Revenue Chart</h3>
  <canvas id="revenueChart" height="120"></canvas>
  <script>
  const revData = {
     labels: ['Full Paid','Partial Paid'],
     datasets: [{label:'Booklets', data:[<?=$fullPaid?>, <?=$partialPaid?>],
       backgroundColor:['#7b61ff','#c8b8ff']}]
  };
  new Chart(document.getElementById('revenueChart'),{type:'bar', data:revData});
  </script>

  <hr>
  <h3>Recent Lotteries</h3>
  <table>
    <tr><th>Name</th><th>ID</th><th>Stage</th><th>Created</th></tr>
    <?php while($r=$recent->fetch_assoc()): ?>
    <tr>
      <td><?=$r['name']?></td>
      <td><?=$r['lottery_id']?></td>
      <td><?=$r['status_stage']?></td>
      <td><?=$r['created_at']?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
</body>
</html>
