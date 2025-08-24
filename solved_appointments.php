<?php
$conn = new mysqli("localhost", "root", "", "vastu_users");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['ajax_search'])) {
    $q = $conn->real_escape_string($_GET['ajax_search']);
    $sql = "SELECT DISTINCT name FROM appointments WHERE status='solved' AND name LIKE '$q%' LIMIT 5";
    $result = $conn->query($sql);
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }
    echo json_encode($suggestions);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Solved Appointments</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f8;
            margin: 0;
            padding: 40px;
            text-align: center;
        }

        h2 {
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }

        .search-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-container {
            position: relative;
        }

        #search {
            padding: 12px 20px;
            font-size: 16px;
            width: 300px;
            border: none;
            border-radius: 25px;
            background: #9adc80ff;
           
            color: white;
            outline: none;
        }

        #search::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-btn {
            background: #86ea88ff;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        
            transition: all 0.2s ease-in-out;
        }

        .search-btn:hover {
            transform: scale(1.05);
            background: #ff5a4d;
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }

        .suggestions div {
            padding: 10px;
            text-align: left;
            cursor: pointer;
        }

        .suggestions div:hover {
            background-color: #f1f1f1;
        }

        .card {
            background-color: #fff;
            padding: 20px 30px;
            margin: 15px auto;
            width: 500px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #e91e63;
            margin-right: 20px;
        }

        .user-name {
            font-size: 18px;
            font-weight: 700;
            color: #6a1b9a;
        }

        .created-time {
            font-size: 14px;
            color: #555;
        }

        .status {
            background-color: #4CAF50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .status::before {
            content: '✔';
            margin-right: 6px;
        }

        .back-btn {
            margin-top: 30px;
            display: inline-block;
            padding: 12px 30px;
            background-color: #6a1b9a;
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #5e1787;
        }
    </style>
</head>
<body>

<h2>Solved Appointments</h2>

<div class="search-wrapper">
    <div class="search-container">
        <input type="text" id="search" placeholder="Search name...">
        <div id="suggestions" class="suggestions"></div>
    </div>
    <button class="search-btn" onclick="performSearch()">Search</button>
</div>
<br>

<div id="appointments-list">
<?php
$sql = "SELECT id, name, created_at FROM appointments WHERE status = 'solved' ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $id = (int) $row['id'];
        $name = htmlspecialchars($row['name']);
        $created = date("d M Y, h:i A", strtotime($row['created_at']));

        echo "
        <a class='card' data-name='{$name}' href='appointment_details.php?id={$id}'>
            <div class='user-info'>
                <img src='avatar.jpg' alt='User'>
                <div>
                    <div class='user-name'>{$name}</div>
                    <div class='created-time'>Created: {$created}</div>
                </div>
            </div>
            <div class='status'>Solved</div>
        </a>";
    }
} else {
    echo "<p>No solved appointments found.</p>";
}
?>
</div>

<a href="appointments.php" class="back-btn">← Back to Dashboard</a>

<script>
document.getElementById('search').addEventListener('input', function () {
    const query = this.value;
    const suggestionBox = document.getElementById('suggestions');
    if (query.length < 1) {
        suggestionBox.style.display = 'none';
        return;
    }

    fetch(`?ajax_search=${query}`)
        .then(res => res.json())
        .then(data => {
            suggestionBox.innerHTML = '';
            if (data.length > 0) {
                data.forEach(name => {
                    const div = document.createElement('div');
                    div.textContent = name;
                    div.onclick = () => {
                        document.getElementById('search').value = name;
                        suggestionBox.style.display = 'none';
                        performSearch();
                    };
                    suggestionBox.appendChild(div);
                });
                suggestionBox.style.display = 'block';
            } else {
                suggestionBox.style.display = 'none';
            }
        });
});

function performSearch() {
    const input = document.getElementById('search').value.toLowerCase();
    const cards = document.querySelectorAll('.card');
    let found = false;

    cards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        if (name.includes(input)) {
            card.style.display = 'flex';
            found = true;
        } else {
            card.style.display = 'none';
        }
    });

    if (!found) {
        document.getElementById('appointments-list').innerHTML = "<p>No matching appointments found.</p>";
    }
}
</script>
</body>
</html>
