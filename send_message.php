<?php
$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? null;
    $message = $_POST['message'] ?? '';
    $products = $_POST['products'] ?? [];

    if (!$id) {
        echo "Invalid appointment ID.";
        exit();
    }

    $productsStr = implode(", ", $products);

    // Update the specific appointment using ID
    $stmt = $conn->prepare("UPDATE appointments SET astrologer_msg = ?, products = ? WHERE id = ?");
    $stmt->bind_param("ssi", $message, $productsStr, $id);

    if ($stmt->execute()) {
        header("Location: appointments.php?msg=sent");
        exit();
    } else {
        echo "Failed to send message.";
    }

    $stmt->close();
}
$conn->close();
?>
