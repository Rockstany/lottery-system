<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$role = $conn->query("SELECT role FROM users WHERE id=$user_id")->fetch_assoc()['role'];
if($role != 'admin'){ header("Location: dashboard.php"); exit; }

if(!is_dir("../config")) mkdir("../config");
$settings_file = "../config/settings.json";

if($_SERVER['REQUEST_METHOD']=='POST'){
  $data = [
    "app_name" => $_POST['app_name'],
    "support_email" => $_POST['support_email'],
    "smtp_host" => $_POST['smtp_host'],
    "smtp_user" => $_POST['smtp_user'],
    "smtp_pass" => $_POST['smtp_pass']
  ];
  file_put_contents($settings_file, json_encode($data, JSON_PRETTY_PRINT));
  $msg = "✅ Settings saved successfully!";
}

$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>System Settings</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="dashboard-header">
  <div class="left"><h2>⚙️ System Settings</h2></div>
  <div class="right"><a href="admin_dashboard.php" class="logout-btn">← Back</a></div>
</header>

<div class="container">
<?php if(!empty($msg)) echo "<script>showToast('$msg');</script>"; ?>

<form method="POST">
  <label>Application Name</label>
  <input type="text" name="app_name" value="<?=$settings['app_name']??''?>" required>
  <label>Support Email</label>
  <input type="email" name="support_email" value="<?=$settings['support_email']??''?>">
  <label>SMTP Host</label>
  <input type="text" name="smtp_host" value="<?=$settings['smtp_host']??''?>">
  <label>SMTP User</label>
  <input type="text" name="smtp_user" value="<?=$settings['smtp_user']??''?>">
  <label>SMTP Password</label>
  <input type="password" name="smtp_pass" value="<?=$settings['smtp_pass']??''?>">
  <button type="submit">Save Settings</button>
</form>
</div>
<script src="../assets/js/toast.js"></script>
</body>
</html>
