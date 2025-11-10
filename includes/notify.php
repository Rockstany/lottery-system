<?php
function add_notification($conn, $user_id, $title, $message) {
  $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)");
  $stmt->bind_param("iss", $user_id, $title, $message);
  $stmt->execute();
}

function send_email_notice($to, $subject, $body) {
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8\r\n";
  $headers .= "From: Lottery System <no-reply@careerplanning.fun>\r\n";
  @mail($to, $subject, $body, $headers);
}
?>
