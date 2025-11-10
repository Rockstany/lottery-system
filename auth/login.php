<?php
session_start();
include("../includes/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mobile = $_POST['mobile'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE mobile = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (hash('sha256', $password) == $row['password_hash']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            header("Location: ../pages/dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Lottery System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
<div class="login-container">
  <h2>Lottery Management Login</h2>
  <form method="POST">
      <input type="text" name="mobile" placeholder="Mobile Number" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
      <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>
  </form>
</div>
</body>
</html>
