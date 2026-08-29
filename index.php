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

// Studio Data - AD Pictures Aesthetic
$studio_name = "AD PICTURES";
$tagline     = "Wedding & Cinematic Film Studio";
$phone       = "+251 908 030 809";
$whatsapp    = "251908030809";
$location    = "Awlo Business Center, 3rd Floor, Bole Medhanialem, Addis Ababa";

$services = [
    [
        "badge"       => "4K · 24FPS · T2.1",
        "title"       => "Cinematic Wedding Films",
        "price"       => "Full Day Coverage",
        "description" => "Directed, lit, and cut like a film — from the first look to the evening celebration."
    ],
    [
        "badge"       => "50mm · f/1.4 · HDR",
        "title"       => "Commercial & Stills",
        "price"       => "Brand Stills & Video",
        "description" => "Product campaigns and commercial film built to capture and hold audience attention."
    ],
    [
        "badge"       => "RAW · 35mm · 60FPS",
        "title"       => "Event & Mels Coverage",
        "price"       => "Multi-Day Packages",
        "description" => "High-energy multi-cam event recording with continuous mood lighting rigs."
    ]
];

$reels = [
    [
        "id" => 1, "category" => "wedding", "meta" => "4K · 24FPS · T2.1", "title" => "THE VOW",
        "type" => "Wedding Film", "image" => "https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 2, "category" => "event", "meta" => "4K · 24FPS · T2.8", "title" => "GRAND ENTRANCE",
        "type" => "Event Film", "image" => "https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 3, "category" => "wedding", "meta" => "4K · 24FPS · T2.1", "title" => "MELS NIGHT",
        "type" => "Cultural Wedding", "image" => "https://images.unsplash.com/photo-1583939003579-730e3918a45a?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 4, "category" => "portrait", "meta" => "50mm · f/1.2 · ISO 100", "title" => "NIGHT WALK",
        "type" => "Cinematic Portrait", "image" => "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1200&q=80"
    ]
];

// Database Fetching & Double Booking Logic
$message = "";
$message_type = "";
$booked_dates = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->query("SELECT booking_date FROM bookings");
    $booked_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    $booked_dates = ["2026-09-05", "2026-09-12"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client_name  = trim(htmlspecialchars($_POST["name"] ?? ""));
    $client_email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $service_type = trim(htmlspecialchars($_POST["service"] ?? ""));
    $booking_date = trim(htmlspecialchars($_POST["date"] ?? ""));
    $notes        = trim(htmlspecialchars($_POST["notes"] ?? ""));

    if ($client_name && $client_email && $booking_date) {
        if (isset($pdo)) {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
            $check_stmt->execute([$booking_date]);

            if ($check_stmt->fetchColumn() > 0) {
                $message = "Date ($booking_date) is reserved. Please pick another date.";
                $message_type = "error";
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO bookings (client_name, client_email, service_type, booking_date, notes) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$client_name, $client_email, $service_type, $booking_date, $notes]);
                
                $message = "Shoot date successfully reserved for $booking_date!";
                $message_type = "success";
                $booked_dates[] = $booking_date;
            }
        } else {
            $message = "Demo Mode: Reserved for $booking_date (No DB connection)";
            $message_type = "success";
            $booked_dates[] = $booking_date;
        }
    } else {
        $message = "Please complete all required fields.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $studio_name ?> — <?= $tagline ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">

    <style>
        :root {
            --bg-black: #050505;
            --bg-card: #0d0d0d;
            --accent-gold: #e2b13c;
            --text-main: #f0f0f0;
            --text-muted: #888888;
            --border-line: rgba(255, 255, 255, 0.12);
            --font-mono: 'Courier New', Courier, monospace;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg-black);
            color: var(--text-main);
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* HEADER */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(5, 5, 5, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-line);
            z-index: 1000;
        }

        nav {
            max-width: 1300px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 30px;
        }

        .logo {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 3px;
            color: #fff;
            text-transform: uppercase;
        }

        .logo span { color: var(--accent-gold); }

        nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: 0.3s;
        }

        nav a:hover { color: #fff; }

        /* CINEMATIC HERO */
        #hero-wrapper {
            position: relative;
            width: 100%;
            min-height: 100vh;
            background: linear-gradient(
                to bottom,
                rgba(5, 5, 5, 0.4) 0%,
                rgba(5, 5, 5, 0.95) 100%
            ), url('https://lightroom-photoshop-tutorials.com/wp-content/uploads/2021/09/Best-Mirrorless-Cameras-for-Professional-Photographers.webp') center/cover no-repeat;
            display: flex;
            align-items: flex-end;
            padding-bottom: 80px;
        }

        #hero {
            max-width: 1300px;
            margin: auto;
            width: 100%;
            padding: 0 30px;
        }

        .lens-tag {
            font-family: var(--font-mono);
            font-size: 13px;
            color: var(--accent-gold);
            letter-spacing: 3px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .hero-text h1 {
            font-size: clamp(48px, 8vw, 90px);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -2px;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        .hero-text p {
            max-width: 550px;
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 35px;
        }

        /* TICKER / MARQUEE */
        .marquee-container {
            width: 100%;
            overflow: hidden;
            background: var(--bg-card);
            border-y: 1px solid var(--border-line);
            padding: 18px 0;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-block;
            animation: marquee 25s linear infinite;
        }

        .marquee-content span {
            font-family: var(--font-mono);
            font-size: 14px;
            letter-spacing: 4px;
            color: var(--text-muted);
            margin-right: 50px;
            text-transform: uppercase;
        }

        .marquee-content span strong { color: var(--accent-gold); }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* SECTIONS */
        section {
            max-width: 1300px;
            margin: auto;
            padding: 120px 30px;
        }

        .section-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 60px;
            border-bottom: 1px solid var(--border-line);
            padding-bottom: 20px;
        }

        .section-hdr h2 {
            font-size: 38px;
            font-weight: 800;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .section-hdr p {
            font-family: var(--font-mono);
            color: var(--accent-gold);
            font-size: 13px;
        }

        /* REELS / GALLERY */
        .reels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 40px;
        }

        .reel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }

        .reel-img {
            width: 100%;
            height: 480px;
            object-fit: cover;
            filter: grayscale(30%);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reel-card:hover .reel-img {
            filter: grayscale(0%);
            transform: scale(1.04);
        }

        .reel-meta {
            padding: 25px;
        }

        .reel-meta .cam-specs {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--accent-gold);
            margin-bottom: 8px;
        }

        .reel-meta h3 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* SERVICES */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .service-box {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            padding: 40px;
            transition: 0.3s;
        }

        .service-box:hover {
            border-color: var(--accent-gold);
        }

        .service-box .badge {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--accent-gold);
            margin-bottom: 20px;
            display: block;
        }

        .service-box h3 {
            font-size: 24px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .service-box p {
            color: var(--text-muted);
            font-size: 15px;
        }

        /* BOOKING FORM */
        .booking-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            padding: 60px;
            max-width: 800px;
            margin: auto;
        }

        .form-group { margin-bottom: 25px; }

        label {
            display: block;
            font-family: var(--font-mono);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        input, select, textarea {
            width: 100%;
            padding: 16px;
            background: #000;
            border: 1px solid var(--border-line);
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-gold);
        }

        .btn-submit {
            background: var(--accent-gold);
            color: #000;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 18px;
            width: 100%;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #fff;
        }

        .alert {
            padding: 15px;
            font-family: var(--font-mono);
            font-size: 13px;
            margin-bottom: 25px;
            text-align: center;
        }
        .alert-success { background: rgba(226, 177, 60, 0.15); color: var(--accent-gold); border: 1px solid var(--accent-gold); }
        .alert-error { background: rgba(255, 0, 0, 0.15); color: #ff5555; border: 1px solid #ff5555; }

        /* FLOATING WHATSAPP BUTTON */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #25d366;
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            z-index: 1000;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .whatsapp-float:hover { transform: scale(1.1); }

        footer {
            border-top: 1px solid var(--border-line);
            padding: 50px 30px;
            text-align: center;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            nav ul { display: none; }
            .reels-grid { grid-template-columns: 1fr; }
            .booking-wrapper { padding: 30px; }
        }
    </style>
</head>
<body>

<header>
    <nav>
        <div class="logo"><?= $studio_name ?><span>.</span></div>
        <ul>
            <li><a href="#hero-wrapper">Film</a></li>
            <li><a href="#reels">Reels</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#booking">Reserve</a></li>
        </ul>
    </nav>
</header>

<div id="hero-wrapper">
    <div id="hero">
        <div class="lens-tag">NOW SHOWING · FEATURED FILM STUDIO</div>
        <div class="hero-text">
            <h1>YOUR DAY.<br>IN MOTION.</h1>
            <p>We don't just record the day — we direct it, light it, and cut it like cinema. Based at Bole Medhanialem, working across Ethiopia.</p>
            <a href="#booking" style="display:inline-block; background: var(--accent-gold); color: #000; padding: 16px 32px; font-weight:800; text-decoration:none; text-transform:uppercase; letter-spacing:2px; font-size:13px;">Book Date</a>
        </div>
    </div>
</div>

<div class="marquee-container">
    <div class="marquee-content">
        <span>THINK · <strong>IMAGINE</strong> · CREATE</span>
        <span>4K CINEMATOGRAPHY · <strong>24FPS</strong></span>
        <span>AWLO BUSINESS CENTER · <strong>BOLE MEDHANIALEM</strong></span>
        <span>THINK · <strong>IMAGINE</strong> · CREATE</span>
        <span>4K CINEMATOGRAPHY · <strong>24FPS</strong></span>
    </div>
</div>

<section id="reels">
    <div class="section-hdr">
        <h2>RECENT REELS</h2>
        <p>DIRECTED & CUT BY AD PICTURES</p>
    </div>

    <div class="reels-grid">
        <?php foreach ($reels as $r): ?>
            <div class="reel-card">
                <img src="<?= $r['image'] ?>" class="reel-img" alt="<?= $r['title'] ?>">
                <div class="reel-meta">
                    <div class="cam-specs"><?= $r['meta'] ?></div>
                    <h3><?= $r['title'] ?></h3>
                    <p style="color: var(--text-muted); font-size: 14px;"><?= $r['type'] ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="services">
    <div class="section-hdr">
        <h2>SERVICES</h2>
        <p>PRODUCTION PACKAGES</p>
    </div>

    <div class="services-grid">
        <?php foreach ($services as $s): ?>
            <div class="service-box">
                <span class="badge"><?= $s['badge'] ?></span>
                <h3><?= $s['title'] ?></h3>
                <p><?= $s['description'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="booking">
    <div class="section-hdr">
        <h2>RESERVE SHOOT</h2>
        <p>DOUBLE-BOOKING GUARD ACTIVE</p>
    </div>

    <div class="booking-wrapper">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" action="#booking">
            <div class="form-group">
                <label>FULL NAME</label>
                <input type="text" name="name" placeholder="Name" required>
            </div>

            <div class="form-group">
                <label>EMAIL ADDRESS</label>
                <input type="email" name="email" placeholder="Email" required>
            </div>

            <div class="form-group">
                <label>SERVICE</label>
                <select name="service">
                    <option>Cinematic Wedding Films</option>
                    <option>Commercial & Stills</option>
                    <option>Event & Mels Coverage</option>
                </select>
            </div>

            <div class="form-group">
                <label>SHOOT DATE (UNAVAILABLE DATES GREYED OUT)</label>
                <input type="text" id="datepicker" name="date" placeholder="Select Available Date..." required readonly>
            </div>

            <div class="form-group">
                <label>NOTES / VENUE DETAILS</label>
                <textarea name="notes" rows="4" placeholder="Event venue and details..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Confirm Reservation</button>
        </form>
    </div>
</section>

<a href="https://wa.me/<?= $whatsapp ?>?text=Hello%20AD%20Pictures,%20I%20want%20to%20inquire%20about%20a%20film%20shoot." class="whatsapp-float" target="_blank" aria-label="WhatsApp">
    <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984 0 1.758.459 3.474 1.33 4.988l-1.416 5.171 5.293-1.389c1.458.796 3.102 1.216 4.78 1.217h.004c5.507 0 9.99-4.478 9.99-9.985 0-2.667-1.037-5.176-2.922-7.062-1.886-1.886-4.394-2.924-7.069-2.924zm5.814 14.182c-.252.707-1.473 1.353-2.023 1.411-.518.055-1.196.082-3.418-.838-2.589-1.072-4.228-3.732-4.357-3.905-.129-.173-1.053-1.401-1.053-2.667 0-1.267.662-1.889.897-2.146.235-.257.514-.322.686-.322.172 0 .344.002.493.009.157.007.368-.06.576.438.214.512.729 1.777.793 1.906.064.129.107.279.021.451-.086.172-.129.279-.257.429-.129.15-.271.335-.387.45-.129.129-.264.27-.114.528.15.257.666 1.098 1.428 1.777.98.874 1.806 1.146 2.064 1.275.257.129.408.107.558-.064.15-.172.643-.75.814-1.007.172-.257.343-.214.579-.129.236.086 1.499.707 1.756.836.257.129.429.193.493.301.064.108.064.621-.188 1.328z"/></svg>
</a>

<footer>
    <p>&copy; <?= date('Y') ?> <?= $studio_name ?> FILM STUDIO · STUDIO: <?= $location ?> · TEL: <?= $phone ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const unavailableDates = <?= json_encode($booked_dates) ?>;

    flatpickr("#datepicker", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: unavailableDates,
        locale: { firstDayOfWeek: 1 }
    });
});
</script>

</body>
</html>
