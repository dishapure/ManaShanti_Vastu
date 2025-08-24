<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php'; // PHPMailer via Composer

// show mysqli errors instead of failing silently
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli('localhost', 'root', '', 'vastu_users');
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Connection failed: ' . $e->getMessage());
}

// -------- helpers --------
function normalize_ids($posted) {
    $ids = [];
    if (is_array($posted)) {
        foreach ($posted as $p) {
            foreach (explode(',', $p) as $part) {
                $part = trim($part);
                if ($part !== '' && ctype_digit($part)) $ids[] = (int)$part;
            }
        }
    } elseif (is_string($posted) && $posted !== '') {
        foreach (explode(',', $posted) as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) $ids[] = (int)$part;
        }
    }
    return array_values(array_unique($ids));
}

function table_exists(mysqli $conn, string $name): bool {
    $nameEsc = $conn->real_escape_string($name);
    $rs = $conn->query("SHOW TABLES LIKE '{$nameEsc}'");
    $exists = $rs->num_rows > 0;
    $rs->free();
    return $exists;
}

/**
 * resolve the actual scheduled table name:
 * prefers `scheduled_appointments`, else `schedules_appointment`.
 * if neither exists, it will CREATE `scheduled_appointments`
 * with safe, minimal columns to let inserts succeed.
 */
function resolve_scheduled_table(mysqli $conn): string {
    if (table_exists($conn, 'scheduled_appointments')) return 'scheduled_appointments';
    if (table_exists($conn, 'schedules_appointment'))  return 'schedules_appointment';

    // create a simple table so inserts don't fail silently
    $sql = "
        CREATE TABLE IF NOT EXISTS `scheduled_appointments` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `appointment_id` INT UNSIGNED NOT NULL,
            `user_name` VARCHAR(255) NOT NULL,
            `scheduled_date` DATE NOT NULL,
            `time_slot` VARCHAR(64) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($sql);
    return 'scheduled_appointments';
}

$SCHEDULED_TABLE = resolve_scheduled_table($conn);

// insert wrapper with strong error reporting
function saveScheduledAppointment(mysqli $conn, string $table, int $appointmentId, string $userName, string $date, string $slot): array {
    $sql = "INSERT INTO `{$table}` (appointment_id, user_name, scheduled_date, time_slot) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isss', $appointmentId, $userName, $date, $slot);
    try {
        $ok = $stmt->execute();
        $stmt->close();
        return [$ok, $ok ? null : 'unknown insert error'];
    } catch (mysqli_sql_exception $e) {
        $err = $e->getMessage();
        $stmt->close();
        return [false, $err];
    }
}

// -------- handle post --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    echo '<pre>'; print_r($_POST); echo '</pre>';

    // IMPORTANT: read the checkboxes as posted
    $appointmentIds = normalize_ids($_POST['appointment_checkbox'] ?? []);
    $date = trim($_POST['appointment_date'] ?? '');
    $slot = trim($_POST['time_slot'] ?? '');

    // soft validation
    if (!$appointmentIds) {
        echo "⚠️ no appointments selected.<br>";
        // do not exit; let page render
    }
    if ($date === '' || $slot === '') {
        echo "⚠️ please select a date and time slot.<br>";
    }

    // basic date sanity: expect YYYY-MM-DD
    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo "⚠️ invalid date format: {$date} (expected YYYY-MM-DD).<br>";
    }

    // tip: your db column for time_slot should be VARCHAR, not TIME
    if ($slot !== '' && strlen($slot) > 64) {
        echo "⚠️ time slot too long, trimming.<br>";
        $slot = substr($slot, 0, 64);
    }

    if ($appointmentIds && $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && $slot !== '') {
        foreach ($appointmentIds as $appointmentId) {
            // fetch user info
            $stmt = $conn->prepare("
               SELECT a.user_id, a.name AS appointment_name, u.email, u.fullname
FROM appointments a
INNER JOIN users u ON a.user_id = u.id
WHERE a.id = ?
LIMIT 1

            ");
            $stmt->bind_param('i', $appointmentId);
            $stmt->execute();
            $stmt->bind_result($userId, $appointmentName, $email, $fullname);

            $found = $stmt->fetch();
            $stmt->close();

            if (!$found) {
                echo "⚠️ appointment id {$appointmentId} not found or user missing.<br>";
                continue;
            }

            // check already scheduled in the resolved table
            $check = $conn->prepare("SELECT COUNT(*) FROM `{$SCHEDULED_TABLE}` WHERE appointment_id = ?");
            $check->bind_param('i', $appointmentId);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            // save
$userDisplayName = $appointmentName ?: $fullname ?: 'User';
[$ok, $err] = saveScheduledAppointment($conn, $SCHEDULED_TABLE, $appointmentId, $userDisplayName, $date, $slot);

            if ($ok) {
                echo "✅ appointment scheduled for {$fullname} on {$date} at {$slot}.<br>";
            } else {
                // show precise db error so you can fix schema/columns fast
                echo "❌ db insert failed for appointment {$appointmentId}: {$err}<br>";
                // skip email if not saved
                continue;
            }

            // email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'disha.trash.boston@gmail.com';
                    $mail->Password = 'blbjzzomlfywwuhp'; // App password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('disha.trash.boston@gmail.com', 'Vastu Consultancies');
                    $mail->addAddress($email, $userDisplayName);
$mail->Body = "Dear {$userDisplayName},<br>Your appointment is confirmed for <b>{$date}</b> at <b>{$slot}</b>.<br><br>Thank you.";

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Appointment is Confirmed';
                    $mail->Body = "Dear {$fullname},<br>Your appointment is confirmed for <b>{$date}</b> at <b>{$slot}</b>.<br><br>Thank you.";

                    $mail->send();
                    echo "📨 email sent to {$email}.<br>";
                } catch (Exception $e) {
                    echo "❌ email sending failed: {$mail->ErrorInfo}<br>";
                }
            } else {
                echo "⚠️ invalid email for user {$fullname}.<br>";
            }
        }
    }

    // helpful: show which table we used
    echo "🔎 using table: {$SCHEDULED_TABLE}<br>";
}

// -------- pending list --------
$result = $conn->query("
    SELECT id, user_id, name, created_at, status
    FROM appointments
    WHERE status != 'solved'
      AND id NOT IN (SELECT appointment_id FROM `{$SCHEDULED_TABLE}`)
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="appointmentscss.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>.disabled{pointer-events:none;opacity:0.4;background:#ccc !important;}</style>
</head>
<body>
<div class="topbar">
    <div class="brand">Vastu Consultancies</div>
    <nav>
        <a href="index.html">Home</a>
        <a href="about.html">About</a>
        <a href="services.html">Services</a>
        <a href="review.html">Reviews</a>
        <a href="logout.php" style="background:#6a1b9a;padding:6px 14px;border-radius:6px;">Logout</a>
    </nav>
</div>
<div class="sidebar">
    <img src="avatar.jpg" alt="User Avatar" class="avatar">
    <h3>Welcome, Admin</h3>
    <a href="appointments.php">All Appointments</a>
    <a href="upcoming_appointments.php">Upcoming Appointments</a>
    <a href="solved_appointments.php">Solved Appointments</a>
    <a href="graphs.php">Graphs</a>
</div>
<div class="main">
    <h1>Hello Ms.Beena Ingole</h1>
    <h2>🔮 All Appointments</h2>
    <form method="POST" action="">
        <div class="content-wrapper" style="display: flex; gap: 40px;">
            <div class="list" style="flex: 1;">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="card">
                            <img src="avatar2.png" alt="Avatar" class="avatar">
                            <div class="info">
                                <h3><?= htmlspecialchars($row['name']); ?></h3>
                                <p>Created: <?= date('d M Y, h:i A', strtotime($row['created_at'])); ?></p>
                            </div>
                            <input type="checkbox"
                               name="appointment_checkbox[]"
                               value="<?= (int)$row['id']; ?>"
                               onchange="updateSelection()"
                               data-name="<?= htmlspecialchars($row['name']); ?>">
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; color: #888;">No appointments found.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="calendar-container" id="calendarBox">
            <h3>📅 Select Date</h3>
            <input type="hidden" name="appointment_date" id="selectedDate" required>
            <input type="hidden" name="time_slot" id="selectedSlot" required>
            <!-- these two hidden fields are not used by PHP anymore, but kept if you need them later -->
            <input type="hidden" name="appointment_ids" id="appointmentIdsInput">
            <input type="hidden" name="user_names" id="userNamesInput">

            <div class="custom-calendar">
                <div class="calendar-header">
                    <select id="monthSelect"></select>
                    <select id="yearSelect"></select>
                </div>
                <div class="calendar-days">
                    <div class="day-name">Sun</div><div class="day-name">Mon</div><div class="day-name">Tue</div>
                    <div class="day-name">Wed</div><div class="day-name">Thu</div><div class="day-name">Fri</div><div class="day-name">Sat</div>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>

            <div class="time-slots" id="timeSlots"><h4>🕒 Select Time Slot:</h4></div>
            <br>
            <button type="submit" name="submit_appointment">Save Appointment & Notify Users</button>
        </div>
    </form>
</div>

<script>
const calendarGrid = document.getElementById("calendarGrid");
const selectedDateInput = document.getElementById("selectedDate");
const monthSelect = document.getElementById("monthSelect");
const yearSelect = document.getElementById("yearSelect");
const timeSlotsContainer = document.getElementById("timeSlots");
const selectedSlotInput = document.getElementById("selectedSlot");

let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
for(let m=0;m<12;m++){
    let opt=new Option(monthNames[m],m);
    if(m===currentMonth)opt.selected=true;
    monthSelect.add(opt);
}
for(let y=currentYear-5;y<=currentYear+5;y++){
    let opt=new Option(y,y);
    if(y===currentYear)opt.selected=true;
    yearSelect.add(opt);
}

function renderCalendar(month, year){
    calendarGrid.innerHTML="";
    const firstDay=new Date(year,month,1).getDay();
    const daysInMonth=new Date(year,month+1,0).getDate();
    for(let i=0;i<firstDay;i++){calendarGrid.appendChild(document.createElement("div"));}
    for(let day=1;day<=daysInMonth;day++){
        const dateObj=new Date(year,month,day);
        const dateStr=`${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const dayDiv=document.createElement("div");
        dayDiv.textContent=day;
        if(dateObj<new Date(today.getFullYear(),today.getMonth(),today.getDate())){
            dayDiv.classList.add("disabled");
        } else {
            dayDiv.addEventListener("click",()=>{
                document.querySelectorAll(".calendar-grid div").forEach(el=>el.classList.remove("selected"));
                dayDiv.classList.add("selected");
                selectedDateInput.value=dateStr;
                generateTimeSlots(dateObj);
            });
        }
        calendarGrid.appendChild(dayDiv);
    }
}

function generateTimeSlots(selectedDate){
    timeSlotsContainer.innerHTML="<h4>🕒 Select Time Slot:</h4>";
    const now=new Date();
    for(let hour=8;hour<=17;hour++){
        if(hour===13) continue;
        let period=hour>=12?"PM":"AM";
        let displayHour=hour%12||12;
        const start=`${displayHour}:00 ${period}`;
        const endHour=hour+1;
        const endPeriod=endHour>=12?"PM":"AM";
        const displayEndHour=endHour%12||12;
        const end=`${displayEndHour}:00 ${endPeriod}`;
        const slotText=`${start} - ${end}`;
        const slotBtn=document.createElement("div");
        slotBtn.textContent=slotText;
        slotBtn.classList.add("time-slot");
        if(selectedDate.toDateString()===now.toDateString() && hour<=now.getHours()){
            slotBtn.classList.add("disabled");
        } else {
            slotBtn.addEventListener("click",()=>{
                document.querySelectorAll(".time-slot").forEach(b=>b.classList.remove("selected"));
                slotBtn.classList.add("selected");
                selectedSlotInput.value=slotText;
            });
        }
        timeSlotsContainer.appendChild(slotBtn);
    }
}

monthSelect.addEventListener("change",()=>{renderCalendar(parseInt(monthSelect.value),parseInt(yearSelect.value));});
yearSelect.addEventListener("change",()=>{renderCalendar(parseInt(monthSelect.value),parseInt(yearSelect.value));});
renderCalendar(currentMonth,currentYear);

function updateSelection(){
    let ids=[], names=[];
    document.querySelectorAll(".list input[type='checkbox']:checked").forEach(cb=>{
        ids.push(cb.value);
        names.push(cb.dataset.name);
    });
    document.getElementById("appointmentIdsInput").value=ids.join(",");
    document.getElementById("userNamesInput").value=names.join(",");
}
document.querySelector("form").addEventListener("submit", updateSelection);
</script>
</body>
</html>
