<?php
$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
    die("DB error: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? '';
    $msg = ''; // Default message

    if (!is_null($id) && in_array($action, ['accept', 'reject'])) {
        // Set proper status and message
        if ($action === 'accept') {
            $status = 'ACCEPTED';
            $msg = 'Your appointment has been accepted.';
        } else {
            $status = 'REJECTED';
            $msg = 'Unfortunately, your appointment has been rejected.';
        }

        // Update status + astrologer message
        $stmt = $conn->prepare("UPDATE appointments SET status = ?, astrologer_msg = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $msg, $id);

        if ($stmt->execute()) {
            header("Location: appointments.php?msg=updated");
            exit();
        } else {
            echo "❌ Failed to update status.";
        }

    } else {
        echo "❌ Invalid input.";
    }
}
$conn->close();
?>
