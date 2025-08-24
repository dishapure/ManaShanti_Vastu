<?php
$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("No appointment ID provided.");
}

$id = (int)$_GET['id'];
$redirect_to = "appointments.php";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['mark_solved'])) {
    $message = $conn->real_escape_string($_POST['message']);
    $products = $conn->real_escape_string($_POST['products'] ?? '');
    $audio_path = null;

    // Handle audio file upload (merged logic)
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/audio/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES['audio_file']['name']);
        $uploadFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $uploadFile)) {
            $audio_path = $uploadFile;
        }
    }

    // Fetch user_id from appointment
    $user_result = $conn->query("SELECT user_id FROM appointments WHERE id = $id");
    if ($user_result && $user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_id = $user_row['user_id'];

        // Update appointments
        $update_stmt = $conn->prepare("UPDATE appointments SET astrologer_msg = ?, products = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $message, $products, $id);
        $update_stmt->execute();

        // Insert into messages
        $insert_msg_stmt = $conn->prepare("INSERT INTO messages (user_id, astrologer_msg, audio_path, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
        $insert_msg_stmt->bind_param("iss", $user_id, $message, $audio_path);
        $insert_msg_stmt->execute();

        // Insert into recommended_products if any
        if (!empty($products)) {
            $insert_stmt = $conn->prepare("INSERT INTO recommended_products (user_id, appointment_id, product_name, recommended_on) VALUES (?, ?, ?, NOW())");
            $insert_stmt->bind_param("iis", $user_id, $id, $products);
            $insert_stmt->execute();
        }

        header("Location: $redirect_to?status=updated");
        exit();
    }
}

// Mark as solved
if (isset($_POST['mark_solved'])) {
    $stmt = $conn->prepare("UPDATE appointments SET status = 'solved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: $redirect_to?status=solved");
    exit();
}

// Fetch appointment
$result = $conn->query("SELECT * FROM appointments WHERE id = $id");
if (!$result || $result->num_rows === 0) {
    die("Appointment not found.");
}
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Details</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f9; padding: 20px; }
    .container { background: #fff; max-width: 800px; margin: auto; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #6a1b9a; }
    button { background-color: #6a1b9a; color: white; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; margin: 5px; }
    button:hover { background-color: #5e1787; }
    .product-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin: 20px 0; }
    .product-card { background: #fafafa; border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; }
    #cart-items { list-style: none; padding-left: 20px; color: #6a1b9a; }
    .remove-btn { background-color: #e53935; font-size: 12px; padding: 4px 8px; margin-left: 10px; }
</style>
</head>
<body>
<?php if (isset($_GET['status'])): ?>
<script>
alert("✅ <?= $_GET['status'] === 'updated' ? 'Appointment updated successfully!' : 'Appointment marked as solved!' ?>");
window.location.href = 'appointments.php';
</script>
<?php endif; ?>

<div class="container">
    <h2>Appointment Details for <?= htmlspecialchars($row['name']) ?></h2>
    <p><strong>DOB:</strong> <?= htmlspecialchars($row['dob']) ?></p>
    <p><strong>Birthplace:</strong> <?= htmlspecialchars($row['birthplace']) ?></p>
    <p><strong>Profession:</strong> <?= htmlspecialchars($row['profession']) ?></p>
    <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone']) ?></p>
    <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($row['status'])) ?></p>
    <p><strong>User Problem:</strong> <?= nl2br(htmlspecialchars($row['user_problem'])) ?></p>

    <?php if ($row['status'] !== 'solved'): ?>
        <form method="POST" style="text-align:center;">
            <input type="hidden" name="mark_solved" value="1">
            <button type="submit" style="background-color: #2e7d32;">✅ Mark as Solved</button>
        </form>
    <?php else: ?>
        <div style="text-align:center; font-size:18px; color:green;">✅ This case is <strong>solved</strong>.</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="main-form" onsubmit="return submitCart();">
        <label for="message">Send Message</label>
        <textarea name="message" id="message" rows="4" required style="width:100%;"><?= htmlspecialchars($row['astrologer_msg']) ?></textarea>

        <h3>🎤 Record Remedies (Audio)</h3>
        <button type="button" id="start">Start Recording</button>
        <button type="button" id="stop">Stop Recording</button>
        <p id="recording-status" style="color: green;"></p>
        <audio id="audio-preview" controls style="display:none;"></audio>
        <input type="file" name="audio_file" id="audioFile" style="display:none;">

        <label>Select Products</label>
        <div class="product-gallery" id="product-list">
        <?php
            $products = [
                "Ketukata" => "🪬", "Budhkata" => "🧠", "Shukra kata" => "💎",
                "Surya kata" => "☀️", "Guru kata" => "📿", "Rahu kata" => "🌑",
                "Mangal katta" => "🔥"
            ];
            foreach ($products as $prod => $icon) {
                echo "<div class='product-card'><div style='font-size:30px;'>$icon</div><p>$prod</p>
                      <button type='button' onclick='addToCart(\"$prod\")'>Add to Cart</button></div>";
            }
        ?>
        </div>

        <strong>🛒 Selected Products:</strong>
        <ul id="cart-items"></ul>
        <input type="hidden" name="products" id="cart-products" value="<?= htmlspecialchars($row['products']) ?>">

        <button type="submit">💌 Send All</button>
    </form>

    <div style="text-align: center; margin-top: 30px;">
        <a href="appointments.php"><button style="background-color: #4B2E83;">← Back</button></a>
    </div>
</div>

<script>
    const cart = new Set();
    const cartList = document.getElementById("cart-items");
    const cartInput = document.getElementById("cart-products");

    function addToCart(product) {
        if (!cart.has(product)) {
            cart.add(product);
            const li = document.createElement("li");
            li.textContent = product;

            const removeBtn = document.createElement("button");
            removeBtn.className = "remove-btn";
            removeBtn.textContent = "Remove";
            removeBtn.onclick = function() {
                cart.delete(product);
                cartList.removeChild(li);
                updateHiddenInput();
            };

            li.appendChild(removeBtn);
            cartList.appendChild(li);
            updateHiddenInput();
        }
    }

    function updateHiddenInput() {
        cartInput.value = Array.from(cart).join(", ");
    }

    function submitCart() {
        updateHiddenInput();
        return true;
    }

    window.onload = function () {
        const existing = cartInput.value.split(",").map(p => p.trim()).filter(p => p);
        existing.forEach(item => addToCart(item));
    };

    let mediaRecorder;
    let audioChunks = [];

    document.getElementById('start').addEventListener('click', async () => {
        try {
            let stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            mediaRecorder.start();
            document.getElementById("recording-status").innerText = "🎙️ Recording...";

            mediaRecorder.addEventListener('dataavailable', e => audioChunks.push(e.data));

            mediaRecorder.addEventListener('stop', () => {
                let audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                let audioUrl = URL.createObjectURL(audioBlob);
                document.getElementById('audio-preview').src = audioUrl;
                document.getElementById('audio-preview').style.display = 'block';

                let file = new File([audioBlob], "recording.webm", { type: "audio/webm" });
                let dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('audioFile').files = dataTransfer.files;

                document.getElementById("recording-status").innerText = "✅ Recording ready.";
            });
        } catch (err) {
            alert("Mic permission needed: " + err);
        }
    });

    document.getElementById('stop').addEventListener('click', () => {
        if (mediaRecorder && mediaRecorder.state !== "inactive") {
            mediaRecorder.stop();
        }
    });
</script>
</body>
</html>
