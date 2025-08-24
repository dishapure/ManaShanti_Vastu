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

// Get user ID from DB
$user_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_query->bind_param("s", $username);
$user_query->execute();
$result = $user_query->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

// Fetch products recommended by astrologer
$stmt = $conn->prepare("SELECT product_name AS products, recommended_on AS created_at FROM recommended_products WHERE user_id = ? ORDER BY recommended_on DESC");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$product_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Recommended Products</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f7f3fb;
      padding: 30px;
      color: #3c1c56;
    }

    .page-heading {
      text-align: center;
      font-size: 36px;
      font-weight: 700;
      margin-bottom: 40px;
      background: linear-gradient(90deg, #6d3c8f, #b38bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 1px 1px 2px rgba(109, 60, 143, 0.2);
    }

    .product-box {
      background-color: #fff;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      max-width: 600px;
      margin: 0 auto 30px;
      transition: 0.3s;
    }

    .product-box:hover {
      transform: scale(1.02);
      box-shadow: 0 0 12px 2px gold;
    }

    .product-list {
      margin-top: 10px;
      padding-left: 20px;
    }

    .timestamp {
      text-align: right;
      font-size: 14px;
      color: #555;
      font-style: italic;
      margin-top: 15px;
    }

    .no-products {
      text-align: center;
      font-size: 20px;
      color: #888;
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
    }

    .back-button:hover {
      background-color: #8548aa;
    }
  </style>
</head>
<body>

  <h2 class="page-heading">🛒 Products Suggested by Astrologer</h2>

  <?php if ($product_result->num_rows > 0): ?>
    <?php while ($row = $product_result->fetch_assoc()): ?>
      <div class="product-box">
        <h3>🧿 Products Recommended:</h3>
        <ul class="product-list">
          <?php
            $items = explode(",", $row['products']);
            foreach ($items as $product) {
              echo "<li>" . htmlspecialchars(trim($product)) . "</li>";
            }
          ?>
        </ul>
        <p class="timestamp">🕒 Recommended on <?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></p>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="no-products">You don't have any product recommendations yet.</p>
  <?php endif; ?>

  <a class="back-button" href="appointment_form.php">← Back to Dashboard</a>

</body>
</html>
