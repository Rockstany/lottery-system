<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$lottery_id = $_GET['id'] ?? 0;

// Fetch taxonomy template
$tax = $conn->query("SELECT t.* FROM taxonomy_templates t 
                     JOIN lotteries l ON l.taxonomy_id=t.id
                     WHERE l.id=$lottery_id")->fetch_assoc();

$l1 = $tax['level1_label'];
$l2 = $tax['level2_label'];
$l3 = $tax['level3_label'];
$levels = $tax['levels'];

// Handle assignment submit
if($_SERVER['REQUEST_METHOD']=='POST'){
  $booklet_id = $_POST['booklet_id'];
  $action = $_POST['action'];

  if($action == 'assign'){
      $name = $_POST['assignee_name'];
      $val1 = $_POST['level1'];
      $val2 = $_POST['level2'];
      $val3 = $_POST['level3'];

      // Check if booklet already assigned
      $check = $conn->query("SELECT id FROM assignments WHERE booklet_id=$booklet_id");
      if($check->num_rows == 0){
        $stmt = $conn->prepare("INSERT INTO assignments (lottery_id, booklet_id, assignee_name, level1, level2, level3, assigned_by)
                                 VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iissssi",$lottery_id,$booklet_id,$name,$val1,$val2,$val3,$user_id);
        $stmt->execute();
        $conn->query("UPDATE booklets SET status='assigned' WHERE id=$booklet_id");
        $msg = "✅ Booklet assigned successfully.";
      } else {
        $msg = "⚠️ Booklet already assigned.";
      }
  }

  if($action == 'unassign'){
      $conn->query("DELETE FROM assignments WHERE booklet_id=$booklet_id");
      $conn->query("UPDATE booklets SET status='unassigned' WHERE id=$booklet_id");
      $msg = "🔄 Booklet marked as Unassigned.";
  }
}
include_once("../includes/log_action.php");
log_action($conn, $user_id, "Assigned Booklet", "Lottery ID $lottery_id, Booklet $booklet_id to $name");

log_action($conn, $user_id, "Unassigned Booklet", "Lottery ID $lottery_id, Booklet $booklet_id");
add_notification($conn,$user_id,"Booklet Assigned","Booklet $booklet_id assigned to $name.");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stage 3 – Distribution / Assignment</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
  <h2>Stage 3: Distribution / Assignment</h2>
</header>

<div class="container">
<?php if(!empty($msg)) echo "<script>showToast('$msg');</script>"; ?>

<h3>Assign Booklet</h3>
<form method="POST">
  <label>Select Booklet:</label>
  <select name="booklet_id" required>
    <option value="">-- Select --</option>
    <?php
      $bks = $conn->query("SELECT * FROM booklets WHERE lottery_id=$lottery_id ORDER BY booklet_no ASC");
      while($b=$bks->fetch_assoc()){
         echo "<option value='{$b['id']}'>Booklet {$b['booklet_no']} ({$b['start_ticket']}-{$b['end_ticket']}) - {$b['status']}</option>";
      }
    ?>
  </select>

  <?php if($levels>=1){ echo "<input type='text' name='level1' placeholder='$l1'>"; } ?>
  <?php if($levels>=2){ echo "<input type='text' name='level2' placeholder='$l2'>"; } ?>
  <?php if($levels>=3){ echo "<input type='text' name='level3' placeholder='$l3'>"; } ?>

  <input type="text" name="assignee_name" placeholder="Assignee Name" required>

  <button type="submit" name="action" value="assign">Assign</button>
  <button type="submit" name="action" value="unassign">Unassign</button>
</form>

<hr>
<h3>📘 Current Assignments</h3>
<table border="1" cellpadding="6" width="100%">
<tr><th>Booklet</th><th>Range</th><th>Assignee</th><th>Level Info</th><th>Status</th></tr>
<?php
$res = $conn->query("SELECT b.booklet_no,b.start_ticket,b.end_ticket,b.status,a.assignee_name,a.level1,a.level2,a.level3
                     FROM booklets b 
                     LEFT JOIN assignments a ON b.id=a.booklet_id
                     WHERE b.lottery_id=$lottery_id ORDER BY b.booklet_no ASC");
while($r=$res->fetch_assoc()){
  $levelsInfo = "{$r['level1']} {$r['level2']} {$r['level3']}";
  echo "<tr>
          <td>{$r['booklet_no']}</td>
          <td>{$r['start_ticket']} - {$r['end_ticket']}</td>
          <td>{$r['assignee_name']}</td>
          <td>{$levelsInfo}</td>
          <td>{$r['status']}</td>
        </tr>";
}
?>
</table>

<form method="POST" action="stage4_collection.php">
  <input type="hidden" name="id" value="<?=$lottery_id?>">
  <button style="margin-top:20px" onclick="return confirm('Proceed to Stage 4?')">Proceed to Stage 4 →</button>
</form>
</div>
<script src="../assets/js/toast.js"></script>
</body>
</html>
