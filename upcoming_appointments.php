<?php
$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch upcoming appointments and exclude those marked as 'solved'
$sql = "
SELECT s.user_name, s.scheduled_date, s.time_slot, s.appointment_id
FROM scheduled_appointments s
JOIN appointments a ON s.appointment_id = a.id
WHERE s.scheduled_date >= CURDATE()
  AND a.status != 'solved'
ORDER BY s.scheduled_date ASC, s.time_slot ASC

        ";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Upcoming Appointments</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f9f9;
      padding: 40px;
    }

    h2 {
      color: #6a1b9a;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    th, td {
      padding: 14px 20px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    th {
      background-color: #f3e5f5;
      color: #6a1b9a;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .no-data {
      margin-top: 20px;
      font-style: italic;
      color: #999;
    }

    .view-button {
      padding: 6px 12px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
    }

    .view-button:hover {
      background-color: #5a1780;
    }
  </style>
</head>
<body>

  <h2>📋 Upcoming Appointments</h2>

  <?php if ($result->num_rows > 0): ?>
    <table>
      <tr>
        <th>User Name</th>
        <th>Date</th>
        <th>Time Slot</th>
        <th>Action</th>
      </tr>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row["user_name"]) ?></td>
          <td><?= htmlspecialchars($row["scheduled_date"]) ?></td>
          <td><?= htmlspecialchars($row["time_slot"]) ?></td>
          <td>
            <a href="appointment_details.php?id=<?= $row['appointment_id'] ?>" class="view-button">
              🔍 View Profile
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p class="no-data">No appointments scheduled yet.</p>
  <?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
