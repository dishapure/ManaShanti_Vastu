<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "vastu_users";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$password_input = $_POST['password'];

// 💥 Special shortcut login for admin user (a/b)
if ($username === 'admin' && $password_input === 'admin') {
    $_SESSION['username'] = 'admin_a';
    $_SESSION['fullname'] = 'Admin User';
    $_SESSION['user_id'] = 0; // Optional if you want to track
    header("Location: appointments.php");
    exit();
}

// 🔐 Normal user flow
$sql = "SELECT * FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password_input, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['user_id'] = $user['id'];
        header("Location: appointment_form.php");
        exit();
    } else {
        echo "<script>alert('❌ Invalid password'); window.location.href='user_dash.html';</script>";
    }
} else {
    echo "<script>alert('❌ User not found'); window.location.href='user_dash.html';</script>";
}

$conn->close();
?>
