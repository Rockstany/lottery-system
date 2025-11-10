<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$role = $conn->query("SELECT role FROM users WHERE id=$user_id")->fetch_assoc()['role'];
if($role != 'admin'){ header("Location: dashboard.php"); exit; }

// --- Backup file name ---
$backup_file = "../backups/lottery_backup_" . date("Ymd_His") . ".sql";
$download_msg = "";

if(isset($_POST['backup_now'])){
  // --- Create backups folder if not exists ---
  if(!is_dir("../backups")) mkdir("../backups");

  // --- Get all table names ---
  $tables = [];
  $result = $conn->query("SHOW TABLES");
  while($row = $result->fetch_array()) $tables[] = $row[0];

  $backup_content = "-- Lottery System SQL Backup - " . date("Y-m-d H:i:s") . "\n\n";
  foreach($tables as $table){
      $table_res = $conn->query("SHOW CREATE TABLE `$table`");
      $table_row = $table_res->fetch_assoc();
      $backup_content .= "\n\n" . $table_row['Create Table'] . ";\n\n";

      $data_res = $conn->query("SELECT * FROM `$table`");
      while($data = $data_res->fetch_assoc()){
          $cols = array_map(fn($v)=>$conn->real_escape_string($v), array_values($data));
          $backup_content .= "INSERT INTO `$table` VALUES ('" . implode("','", $cols) . "');\n";
      }
  }

  file_put_contents($backup_file, $backup_content);
  $download_msg = "✅ Backup created successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin | Backup & Export</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="dashboard-header">
  <div class="left"><h2>🗄️ Backup & Export</h2></div>
  <div class="right"><a href="admin_dashboard.php" class="logout-btn">← Back</a></div>
</header>

<div class="container">
<?php if($download_msg) echo "<p class='success'>$download_msg</p>"; ?>

<h3>Create Full Database Backup</h3>
<form method="POST">
  <button name="backup_now">Download Full SQL Backup</button>
</form>

<?php
if(file_exists($backup_file)){
  echo "<p><a href='$backup_file' download class='dup-btn'>📥 Download Backup File</a></p>";
}
?>

<hr>
<h3>Export Table Data (CSV)</h3>
<form method="POST" action="../export_table.php">
  <select name="table_name" required>
    <option value="">-- Select Table --</option>
    <option value="users">Users</option>
    <option value="lotteries">Lotteries</option>
    <option value="booklets">Booklets</option>
    <option value="payments">Payments</option>
    <option value="assignments">Assignments</option>
  </select>
  <button type="submit">Export CSV</button>
</form>
</div>
</body>
</html>
