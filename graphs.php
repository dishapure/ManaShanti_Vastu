<?php
$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Count solved and pending from appointments table
$solved = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status = 'solved'")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status != 'solved' OR status IS NULL")->fetch_assoc()['c'];

// Define expected product names
$all_products = ['Yantra', 'Camphor', 'Lamp', 'Crystal', 'Pyramid', 'Vastu Salt', 'Ketukata', 'Budhkata', 'Shukra kata', 'Surya kata', 'Guru kata', 'Rahu kata', 'Mangal katta'];

// Count each product appearance from recommended_products table
$product_counts = [];
foreach ($all_products as $prod) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM recommended_products WHERE product_name LIKE ?");
    $like = "%$prod%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $product_counts[$prod] = (int)$res->fetch_assoc()['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Graphs</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: sans-serif;
      background: #faf8fd;
      padding: 40px;
    }
    h1 {
      text-align: center;
      color: #6a1b9a;
      margin-bottom: 30px;
    }
    .chart-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      width: 90%;
      max-width: 700px;
      margin: 0 auto 40px auto;
    }
    .back-btn {
      display: inline-block;
      margin-bottom: 30px;
      background: #6a1b9a;
      color: white;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 6px;
    }
    canvas {
      width: 100% !important;
      height: 300px !important;
    }
  </style>
</head>
<body>
  <a class="back-btn" href="appointments.php">← Back to Dashboard</a>
  <h1>📊 Appointment Analytics</h1>

  <!-- Solved vs Pending Bar Chart -->
  <div class="chart-card">
    <h3 style="color:#6a1b9a;">Solved vs Pending</h3>
    <canvas id="statusChart"></canvas>
  </div>

  <!-- Recommended Products Line Chart -->
  <div class="chart-card">
    <h3 style="color:#6a1b9a;">Recommended Products</h3>
    <canvas id="productsChart"></canvas>
  </div>

  <script>
    // Solved vs Pending (Bar Chart)
    new Chart(document.getElementById("statusChart"), {
      type: 'bar',
      data: {
        labels: ['Solved', 'Pending'],
        datasets: [{
          label: 'Appointments',
          data: [<?= $solved ?>, <?= $pending ?>],
          backgroundColor: ['#4caf50', '#f44336']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, precision: 0 }
        }
      }
    });

    // Recommended Products (Line Chart)
    new Chart(document.getElementById("productsChart"), {
      type: 'line',
      data: {
        labels: <?= json_encode(array_keys($product_counts)) ?>,
        datasets: [{
          label: 'Times Recommended',
          data: <?= json_encode(array_values($product_counts)) ?>,
          borderColor: '#7e57c2',
          backgroundColor: 'rgba(126, 87, 194, 0.2)',
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, precision: 0 },
          x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } }
        }
      }
    });
  </script>
</body>
</html>
