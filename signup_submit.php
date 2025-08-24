<?php
// File: C:\xampp\htdocs\vastu_website\signup_submit.php

$host = "localhost";
$user = "root";
$password = "";
$db = "vastu_users"; // Make sure this is already created

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fullname = $_POST['fullname'];
$username = $_POST['username'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users (fullname, username, email, phone, password)
        VALUES ('$fullname', '$username', '$email', '$phone', '$password')";

if ($conn->query($sql) === TRUE) {
    echo "Signup successful! <a href='user_dash.html'>Click here to login</a>";

} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
