<?php
session_start();
include("../includes/db.php");
include("../includes/log_action.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");
$uid = $_SESSION['user_id'];
$role = $conn->query("SELECT role FROM users WHERE id=$uid")->fetch_assoc()['role'];
if($role!='admin'){ header("Location: dashboard.php"); exit; }

// --- Filters
$q = $_GET['q'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$where = "WHERE 1";
if($q) $where .= " AND (u.name LIKE '%$q%' OR l.action LIKE '%$q%' OR l.details LIKE '%$q%')";
if($from) $where .= " AND DATE(l.created_at) >= '$from'";
if($to)   $where .= " AND DATE(l.created_at) <= '$to'";

$res = $conn->query("SELECT l.*, u.name FROM logs l LEFT JOIN users u ON l.user_id=u.id $where ORDER BY l.id DESC LIMIT 200");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin | Activity Logs</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="dashboard-header">
  <div class="left"><h2>🕓 Activity Logs</h2></div>
  <div class="right"><a href="admin_dashboard.php" class="logout-btn">← Back</a></div>
</header>

<div class="filter-bar">
  <form method="GET">
    <input type="text" name="q" placeholder="Search user or action" value="<?=htmlspecialchars($q)?>">
    <input type="date" name="from" value="<?=htmlspecialchars($from)?>">
    <input type="date" name="to" value="<?=htmlspecialchars($to)?>">
    <button type="submit">Filter</button>
  </form>
</div>

<div class="container">
<table>
<tr><th>User</th><th>Action</th><th>Details</th><th>Time</th></tr>
<?php while($r=$res->fetch_assoc()): ?>
<tr>
  <td><?=$r['name']?></td>
  <td><?=$r['action']?></td>
  <td><?=$r['details']?></td>
  <td><?=$r['created_at']?></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
