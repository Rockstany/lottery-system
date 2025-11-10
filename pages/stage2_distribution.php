<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$lottery_id = $_GET['id'] ?? 0;

// Handle form submission
if($_SERVER['REQUEST_METHOD']=='POST'){
  $action = $_POST['action'];

  if($action == 'create'){
    $levels = $_POST['levels'];
    $name   = $_POST['template_name'];
    $l1 = $_POST['level1'] ?? NULL;
    $l2 = $_POST['level2'] ?? NULL;
    $l3 = $_POST['level3'] ?? NULL;

    $stmt = $conn->prepare("INSERT INTO taxonomy_templates (user_id,name,levels,level1_label,level2_label,level3_label)
      VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isisss",$user_id,$name,$levels,$l1,$l2,$l3);
    $stmt->execute();
    $tid = $conn->insert_id;

    $conn->query("UPDATE lotteries SET taxonomy_id=$tid, status_stage=2 WHERE id=$lottery_id");
    $msg = "Template created and linked to this lottery.";

    $msg = "Template created and linked to this lottery.";
    header("Location: stage3_distribution_assign.php?id=".$lottery_id);
    exit;
  }
  if($action == 'reuse'){
    $reuse = $_POST['reuse_template'];
    $conn->query("UPDATE lotteries SET taxonomy_id=$reuse, status_stage=2 WHERE id=$lottery_id");
    $msg = "Existing template linked to this lottery.";
    header("Location: stage3_distribution_assign.php?id=".$lottery_id);
    exit;
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stage 2 – Distribution Design</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
  <h2>Stage 2: Distribution Design</h2>
</header>

<div class="container">
<?php if(!empty($msg)) echo "<script>showToast('$msg');</script>"; ?>

<h3>Create a New Distribution Template</h3>
<form method="POST">
  <input type="hidden" name="action" value="create">
  <input type="text" name="template_name" placeholder="Template Name (e.g. India 3 Level)" required>
  <label>Number of Levels:</label>
  <select name="levels" id="levels" onchange="showLevels(this.value)">
     <option value="1">1</option>
     <option value="2">2</option>
     <option value="3">3</option>
  </select>

  <div id="levelInputs">
    <input type="text" name="level1" placeholder="Level 1 Label (e.g. State)" required>
    <input type="text" name="level2" placeholder="Level 2 Label (optional)">
    <input type="text" name="level3" placeholder="Level 3 Label (optional)">
  </div>

  <button type="submit" onclick="return confirm('Once submitted, you cannot modify Stage 2. Proceed?')">Save Template</button>
</form>

<hr>
<h3>Or Reuse Existing Template</h3>
<form method="POST">
  <input type="hidden" name="action" value="reuse">
  <select name="reuse_template" required>
    <option value="">-- Select Saved Template --</option>
    <?php
      $res = $conn->query("SELECT * FROM taxonomy_templates WHERE user_id=$user_id ORDER BY id DESC");
      while($r=$res->fetch_assoc()){
        echo "<option value='{$r['id']}'>{$r['name']} ({$r['levels']}-level)</option>";
      }
    ?>
  </select>
  <button type="submit" onclick="return confirm('Once submitted, you cannot modify Stage 2. Proceed?')">Reuse Template</button>
</form>
</div>

<script>
function showLevels(val){
  const l2=document.querySelector('input[name="level2"]');
  const l3=document.querySelector('input[name="level3"]');
  l2.style.display = (val>1)?'block':'none';
  l3.style.display = (val>2)?'block':'none';
}
</script>
<script src="../assets/js/toast.js"></script>
</body>
</html>
