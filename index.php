<?php
// =====================================
// DATABASE CONFIGURATION (PDO)
// =====================================
$host    = '127.0.0.1';
$db      = 'studio_db';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$message = "";
$message_type = "";
$booked_dates = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch all existing booked dates from the database
    $stmt = $pdo->query("SELECT booking_date FROM bookings");
    $booked_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (\PDOException $e) {
    // Graceful fallback for demo setup
    $booked_dates = ["2026-09-05", "2026-09-12"]; 
}

// =====================================
// FORM SUBMISSION & BACKEND VALIDATION
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($pdo)) {
    $client_name  = trim(htmlspecialchars($_POST["name"] ?? ""));
    $client_email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $service_type = trim(htmlspecialchars($_POST["service"] ?? ""));
    $booking_date = trim(htmlspecialchars($_POST["date"] ?? ""));
    $notes        = trim(htmlspecialchars($_POST["notes"] ?? ""));

    if ($client_name && $client_email && $booking_date) {
        
        // SERVER-SIDE DOUBLE BOOKING GUARD: Check if date was booked while user was filling the form
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
        $check_stmt->execute([$booking_date]);
        $already_booked = $check_stmt->fetchColumn();

        if ($already_booked > 0) {
            $message = "Selected date ($booking_date) is already booked. Please select another date.";
            $message_type = "error";
        } else {
            // Insert reservation securely
            $insert_stmt = $pdo->prepare("INSERT INTO bookings (client_name, client_email, service_type, booking_date, notes) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->execute([$client_name, $client_email, $service_type, $booking_date, $notes]);
            
            $message = "Session successfully reserved for $booking_date!";
            $message_type = "success";
            
            // Refresh disabled dates list
            $booked_dates[] = $booking_date;
        }
    } else {
        $message = "Please fill in all required fields with valid details.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Booking | Dynamic Datepicker</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">

    <style>
        body {
            background-color: #090d16;
            color: #f3f4f6;
            font-family: Arial, sans-serif;
            padding: 50px 20px;
        }

        .booking-card {
            background: #121929;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 600px;
            margin: auto;
            padding: 35px;
            border-radius: 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            background: #0b101d;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box;
        }

        .btn-submit {
            background: #f59e0b;
            color: #000;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    </style>
</head>
<body>

<div class="booking-card">
    <h2>Reserve Studio Time</h2>
    <p style="color: #9ca3af; margin-bottom: 25px;">Dates highlighted in gray are unavailable.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="service">Service Type</label>
            <select id="service" name="service">
                <option>Portrait & Headshots</option>
                <option>Product Photography</option>
                <option>Studio Space Rental</option>
            </select>
        </div>

        <div class="form-group">
            <label for="date">Select Available Date</label>
            <input type="text" id="date" name="date" placeholder="Click to select date..." required readonly>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>

        <button type="submit" class="btn-submit">Submit Reservation</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Array of booked dates injected directly from PHP backend
    const unavailableDates = <?= json_encode($booked_dates) ?>;

    flatpickr("#date", {
        dateFormat: "Y-m-d",
        minDate: "today", // Prevents selecting past dates
        disable: unavailableDates, // Grays out unavailable dates on the calendar
        locale: {
            firstDayOfWeek: 1
        }
    });
});
</script>

</body>
</html>
