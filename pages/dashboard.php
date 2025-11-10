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
<footer>
  <p>&copy; <?php echo date("Y"); ?> Lottery System</p>
</footer>
</body>
</html>
