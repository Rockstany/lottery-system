<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$role = $conn->query("SELECT role FROM users WHERE id=$user_id")->fetch_assoc()['role'];
if($role != 'admin'){ header("Location: dashboard.php"); exit; }

// --- Add new user ---
if(isset($_POST['add_user'])){
  $name = $_POST['name'];
  $mobile = $_POST['mobile'];
  $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $urole = $_POST['role'];
  $conn->query("INSERT INTO users (name,mobile,password_hash,role,status) VALUES
                ('$name','$mobile','$pass','$urole','active')");
  $msg = "✅ User added successfully.";
}

// --- Update role/status ---
if(isset($_POST['update_user'])){
  $uid = $_POST['uid'];
  $role = $_POST['role'];
  $status = $_POST['status'];
  $conn->query("UPDATE users SET role='$role', status='$status' WHERE id=$uid");
  $msg = "🔄 User updated.";
}

// --- Reset password ---
if(isset($_POST['reset_pass'])){
  $uid = $_POST['uid'];
  $newHash = password_hash('12345', PASSWORD_DEFAULT);
  $conn->query("UPDATE users SET password_hash='$newHash' WHERE id=$uid");
  $msg = "🔑 Password reset to 12345.";
}

// --- Delete user ---
if(isset($_POST['delete_user'])){
  $uid = $_POST['uid'];
  if($uid != $user_id){ // prevent self-delete
    $conn->query("DELETE FROM users WHERE id=$uid");
    $msg = "🗑️ User deleted.";
  } else $msg = "⚠️ Cannot delete your own account.";
}

// --- Fetch all users ---
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin | User Management</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="dashboard-header">
  <div class="left"><h2>👥 User Management</h2></div>
  <div class="right"><a href="admin_dashboard.php" class="logout-btn">← Back to Dashboard</a></div>
</header>

<div class="container">
<?php if(!empty($msg)) echo "<p class='success'>$msg</p>"; ?>

<h3>Add New User</h3>
<form method="POST">
  <input type="text" name="name" placeholder="Full Name" required>
  <input type="text" name="mobile" placeholder="Mobile Number" required>
  <input type="password" name="password" placeholder="Initial Password" required>
  <select name="role">
    <option value="user">User</option>
    <option value="admin">Admin</option>
  </select>
  <button type="submit" name="add_user">Add User</button>
</form>

<hr>
<h3>All Users</h3>
<table>
<tr><th>Name</th><th>Mobile</th><th>Role</th><th>Status</th><th>Actions</th></tr>
<?php while($u=$users->fetch_assoc()): ?>
<tr>
  <td><?=$u['name']?></td>
  <td><?=$u['mobile']?></td>
  <td><?=$u['role']?></td>
  <td><?=$u['status']?></td>
  <td>
    <form method="POST" style="display:inline;">
      <input type="hidden" name="uid" value="<?=$u['id']?>">
      <select name="role">
        <option <?=$u['role']=='user'?'selected':''?>>user</option>
        <option <?=$u['role']=='admin'?'selected':''?>>admin</option>
      </select>
      <select name="status">
        <option <?=$u['status']=='active'?'selected':''?>>active</option>
        <option <?=$u['status']=='disabled'?'selected':''?>>disabled</option>
      </select>
      <button name="update_user">Save</button>
      <button name="reset_pass" onclick="return confirm('Reset password to 12345?')">Reset Pass</button>
      <button name="delete_user" onclick="return confirm('Delete this user?')">Delete</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
