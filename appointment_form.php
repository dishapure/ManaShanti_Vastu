<?php
session_start();

if (!isset($_SESSION['username'])) {
  header("Location: user_dash.html");
  exit();
}

// Check if user's appointment is accepted
$message = "No messages from the astrologer at this time.";
if (file_exists("appointments.txt")) {
  $username = $_SESSION['username'];
  $lines = file("appointments.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (strpos($line, $username) !== false && strpos($line, "status:ACCEPT") !== false) {
      $message = "Your appointment has been accepted by the astrologer.";
      break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    html {
      scroll-behavior: smooth;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f7f3fb;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #4b2a68;
      padding: 10px 20px;
      color: white;
    }
    .logo-group {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .logo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
    }
    .nav-links {
      list-style: none;
      display: flex;
      gap: 20px;
      margin: 0;
      padding: 0;
    }
    .nav-links li a {
      color: white;
      text-decoration: none;
    }
    .nav-links li a.cta-button {
      background-color: #6d3c8f;
      padding: 8px 16px;
      border-radius: 10px;
    }
    .container {
      display: flex;
    }
    .sidebar {
      background-color: #4b2a68;
      color: #fff;
      width: 250px;
      height: 120vh;
      padding: 20px;
      box-sizing: border-box;
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      overflow-y: auto;
    }

    .sidebar img {
      margin-bottom: 20px;
    }
    .sidebar h2 {
      font-size: 18px;
      margin-bottom: 20px;
    }
    .sidebar button {
      display: block;
      width: 100%;
      margin: 10px 0;
      padding: 12px;
      background-color: #6d3c8f;
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
    }
    .sidebar button:hover {
      background-color: #8548aa;
    }
    .main {
      margin-left: 270px;
      padding: 40px;
      box-sizing: border-box;
      flex-grow: 1;
    }
    .form-section, .messages-section, .products-section, .past-appointments-section {
      background: #fff;
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 40px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    h2 {
      margin-top: 0;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
    }
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .submit-btn {
      padding: 10px 20px;
      background-color: #6d3c8f;
      color: #fff;
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }
    .submit-btn:hover {
      background-color: #8548aa;
    }
    /* === Quick Mobile Fixes for Appointment Page === */
@media (max-width: 768px) {
  body {
    padding-top: 70px;
  }

  /* Navbar adjustments */
  .navbar {
    flex-direction: column;
    align-items: flex-start;
    padding: 10px 15px;
    gap: 10px;
  }
  .nav-links {
    flex-direction: column;
    width: 100%;
    gap: 8px;
  }
  .nav-links li a.cta-button {
    width: 100%;
    text-align: center;
  }

  /* Sidebar becomes top menu */
  .sidebar {
    position: relative;
    width: 100%;
    height: auto;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    padding: 10px 15px;
    justify-content: space-around;
  }
  .sidebar h2 {
    width: 100%;
    text-align: center;
  }
  .sidebar img {
    margin: 10px auto;
    display: block;
  }
  .sidebar button {
    flex: 1 1 45%;
    margin: 5px;
  }

  /* Main content takes full width */
  .main {
    margin-left: 0;
    padding: 20px 10px;
  }

  /* Form adjustments */
  .form-section {
    padding: 20px;
    margin-bottom: 20px;
  }
  .form-group input,
  .form-group select,
  textarea {
    font-size: 1rem;
    padding: 8px;
  }
  .submit-btn {
    width: 100%;
    padding: 12px 0;
    font-size: 1rem;
  }
}

  </style>
</head>
<body>
  <header>
    <div class="navbar">
      <a href="index.html" class="logo-group" style="text-decoration: none; color: inherit;">
        <img src="vastu_logo.jpg" alt="Vastu Logo" class="logo" />
        <span class="company-name">Vastu Consultancies</span>
      </a>
      <ul class="nav-links">
        <li><a href="index.html">Home</a></li>
        <li><a href="about.html">About Us</a></li>
        <li><a href="#">Our Services</a></li>
        <li><a href="review.html">Reviews</a></li>
        <li><a href="logout.php" class="cta-button">Logout (<?= $_SESSION['username'] ?>)</a></li>
      </ul>
    </div>
  </header>

  <div class="container">
    <div class="sidebar">
      <img src="avatar.jpg" alt="Avatar" style="width: 80px; border-radius: 50%; margin-top: 80px;" />

      <h2>Welcome, <?= $_SESSION['username'] ?></h2>
      <button onclick="scrollToSection('form-section')">Book Appointment</button>
      <button onclick="window.location.href='view_past_appointments.php'">Past Appointments</button>
      <button onclick="window.location.href='msg.php'">Messages</button>
      <button onclick="window.location.href='products.php'">Products</button>
    </div>

    <div class="main">
      <div id="form-section" class="form-section">
        <h2>Book Your Appointment</h2>
        <form id="appointmentForm"  method="POST" onsubmit="return false;">
          <div class="form-group"><label>Name</label><input type="text" name="name" required /></div>
          <div class="form-group"><label>Date of Birth & Time (Format : DD/MM/YYYY hh:mm)</label><input name="dob" required /></div>
          <div class="form-group"><label>Birth Place</label><input type="text" name="birthplace" required /></div>
          <div class="form-group"><label>Who Referred You?</label><input type="text" name="referred_by" /></div>
          <div class="form-group"><label>Profession</label><input type="text" name="profession" /></div>
           <label>Describe Your Problem</label>
            <textarea name="user_problem" rows="4" placeholder="Briefly describe the issue you want to get solved..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;" required></textarea>
          <div class="form-group"><label>Location for Vastu</label><input type="text" name="location" required /></div>
          <div class="form-group">
            <label>Property Type</label>
            <select name="property_type" required>
              <option value="">-- Please Select --</option>
              <option value="commercial">Commercial</option>
              <option value="residential">Residential</option>
            </select>
          </div>
          <div class="form-group"><label>Phone</label><input type="tel" name="phone" required /></div>
          <div class="form-group">
            <label>Meeting Mode</label>
            <label><input type="radio" name="mode" value="online" checked /> Online</label>
            <label><input type="radio" name="mode" value="offline" /> Offline</label>
          </div>
          <button type="submit" class="submit-btn">Submit Appointment</button>
        </form>
      </div>
<script>
document.getElementById("appointmentForm").addEventListener("submit", function (e) {
  e.preventDefault(); // ✅ STOP default submit
  const form = e.target;
  const submitButton = form.querySelector("button[type='submit']");
  submitButton.disabled = true;

  const formData = new FormData(form);

  fetch("submit.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.status === "success") {
      window.location.href = "appointment_form.php"; // 🔁 Redirect
    }
  })
  .catch(err => {
    alert("Something went wrong.");
    console.error(err);
  })
  .finally(() => {
    submitButton.disabled = false;
  });
});
</script>


</html>
