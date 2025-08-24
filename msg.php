<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: user_dash.html");
  exit();
}

$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];

// Get user ID
$user_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_query->bind_param("s", $username);
$user_query->execute();
$result = $user_query->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

// Fetch messages
$stmt = $conn->prepare("SELECT astrologer_msg, audio_path, created_at FROM messages 
                        WHERE user_id = ? AND (astrologer_msg != '' OR audio_path IS NOT NULL) 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$msg_result = $stmt->get_result();

// Fetch scheduled appointment (latest one)
$appt_stmt = $conn->prepare("SELECT scheduled_date, time_slot 
                             FROM scheduled_appointments 
                             WHERE user_name = ? 
                             ORDER BY scheduled_date DESC LIMIT 1");
$appt_stmt->bind_param("s", $username);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();
$appointment = $appt_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Messages</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f7f3fb; padding: 20px; }
    .page-heading { text-align: center; font-size: 36px; font-weight: 700; margin-bottom: 40px; }
    .message-box { width: 80%; margin: auto; background-color: white; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.08); }
    .timestamp { text-align: right; font-size: 14px; color: #555; margin-top: 10px; font-style: italic; }
    .no-messages { text-align: center; font-size: 18px; color: #888; }
  </style>
</head>
<body>
  <h2 class="page-heading">🔮 Messages from Astrologer 🔮</h2>

  <?php if ($msg_result->num_rows > 0): ?>
    <?php while ($row = $msg_result->fetch_assoc()): ?>
      <div class="message-box">
        <?php if (!empty($row['astrologer_msg'])): ?>
          <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($row['astrologer_msg'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($appointment)): ?>
          <p><strong>📅 Time Slot : </strong> -
             <?= date("d M Y", strtotime($appointment['scheduled_date'])) ?> at <?= htmlspecialchars($appointment['time_slot']) ?>.</p>
        <?php endif; ?>

        <?php if (!empty($row['audio_path'])): ?>
          <p><strong>🎧 Audio Remedy:</strong></p>
          <audio controls>
            <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/webm">
            Your browser does not support the audio element.
          </audio>
        <?php endif; ?>

        <p class="timestamp">📅 Sent on <?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></p>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="no-messages">No messages from the astrologer at this time.</p>
  <?php endif; ?>

  <a class="back-button" href="appointment_form.php">← Back to Dashboard</a>
</body>
</html>
