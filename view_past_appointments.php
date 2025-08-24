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
$user_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_query->bind_param("s", $username);
$user_query->execute();
$result = $user_query->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

$stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Past Appointments</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f7f3fb;
      margin: 0;
      padding: 20px;
      color: #3c1c56;
    }

    h2.page-heading {
      text-align: center;
      font-size: 36px;
      font-weight: 700;
      margin-bottom: 40px;
      color: #4b2a68;
      background: linear-gradient(90deg, #6d3c8f, #b38bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 1px 1px 2px rgba(109, 60, 143, 0.2);
      letter-spacing: 1px;
      animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    table {
      width: 95%;
      margin: auto;
      border-collapse: collapse;
      background-color: #fff;
      border: 2px solid #6d3c8f;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
      border-radius: 10px;
      overflow: hidden;
    }

    th, td {
      padding: 12px 15px;
      border: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: #6d3c8f;
      color: #fff;
    }

    tr:hover {
      background-color: #fff8dc;
      box-shadow: 0 0 10px 2px gold;
      transition: all 0.3s ease;
      transform: scale(1.01);
      z-index: 1;
      position: relative;
    }

    table tr {
      transition: all 0.3s ease;
    }

    .back-button {
      display: block;
      width: fit-content;
      margin: 40px auto 0;
      padding: 12px 24px;
      font-size: 16px;
      background-color: #6d3c8f;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }

    .back-button:hover {
      background-color: #8548aa;
    }

    @media screen and (max-width: 768px) {
      table {
        font-size: 14px;
      }

      th, td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>

<h2 class="page-heading">Your Past Appointments</h2>

<table>
  <tr>
    <th>Name</th>
    <th>DOB</th>
    <th>Birthplace</th>
    <th>Referred By</th>
    <th>Profession</th>
    <th>Location</th>
    <th>Property Type</th>
    <th>Phone</th>
    <th>Mode</th>
    <th>Status</th>
    <th>Date</th>
    <th>Time</th>
    <th>Problem</th>
  </tr>

  <?php while ($row = $appointments->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['name']) ?></td>
      <td><?= htmlspecialchars($row['dob']) ?></td>
      <td><?= htmlspecialchars($row['birthplace']) ?></td>
      <td><?= htmlspecialchars($row['referred_by']) ?></td>
      <td><?= htmlspecialchars($row['profession']) ?></td>
      <td><?= htmlspecialchars($row['location']) ?></td>
      <td><?= htmlspecialchars($row['property_type']) ?></td>
      <td><?= htmlspecialchars($row['phone']) ?></td>
      <td><?= htmlspecialchars($row['mode']) ?></td>
      <td><?= htmlspecialchars($row['status']) ?></td>
      <td><?= htmlspecialchars($row['date']) ?></td>
      <td><?= htmlspecialchars($row['time']) ?></td>
      <td><?= nl2br(htmlspecialchars($row['user_problem'])) ?></td>
    </tr>
  <?php endwhile; ?>
</table>

<a class="back-button" href="appointment_form.php">← Back to Appointment Form</a>
</body>
</html>
