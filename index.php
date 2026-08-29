<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body { font-family: sans-serif; text-align: center; background: #f0f2f5; margin: 0; padding: 20px; }
        .score-box { font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #333; }
        .tap-btn { padding: 80px; font-size: 20px; color: #fff; background-color: #3b82f6; border: none; border-radius: 50%; cursor: pointer; }
        .tap-btn:active { background-color: #2563eb; transform: scale(0.95); }
    </style>
</head>
<body>
    <div class="score-box">Score: <span id="score">0</span></div>
    <button class="tap-btn" id="tap-btn">TAP!</button>

    <script>
        const tg = window.Telegram.WebApp;
        tg.expand();
        const user = tg.initDataUnsafe.user;
        let score = 0;
        document.getElementById('tap-btn').addEventListener('click', () => {
            score++;
            document.getElementById('score').innerText = score;
        });

        window.addEventListener('beforeunload', () => {
            if (user && score > 0) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `submit_score=1&user_id=${user.id}&username=${user.username || 'unknown'}&score=${score}`
                });
            }
        });
    </script>

    <!-- <?php
    // if (isset($_POST['submit_score'])) {
        // $user_id = $_POST['user_id'];
        // $username = $_POST['username'];
        // $score = $_POST['score'];
        // $data = "User ID: $user_id | Username: $username | Score: $score\n";
        // file_put_contents('scores.txt', $data, FILE_APPEND);
        // exit; -->
    // }
    // ?>
</body>
</html> -->

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

// Studio Data
$studio_name = "Photo luqas Vision";
$tagline     = "Professional Photo & Video Home Studio";
$phone       = "+251 900 000 000";
$location    = "Bonga Kaffa, Ethiopia";

$services = [
    [
        "title"       => "Portrait & Headshots",
        "price"       => "Br 1,500 / session",
        "description" => "High-end creative portraits with customized backdrop lighting setups."
    ],
    [
        "title"       => "Product Photography",
        "price"       => "Br 2,000 / batch",
        "description" => "Clean, high-resolution product photography optimized for e-commerce and social ads."
    ],
    [
        "title"       => "Content Creator Studio Rental",
        "price"       => "Br 800 / hour",
        "description" => "Full studio space access with softboxes, RGB accent lights, ring lights, and background panels."
    ]
];

$gallery_items = [
    [
        "id" => 1, "category" => "portraits", "title" => "Neon Glow Portrait",
        "caption" => "Dual-tone RGB softbox setup with dark velvet backdrop.",
        "image" => "https://picsum.photos/id/1027/1200/800"
    ],
    [
        "id" => 2, "category" => "products", "title" => "Luxury Watch Showcase",
        "caption" => "Macro product shot utilizing continuous LED ring lighting.",
        "image" => "https://picsum.photos/id/1060/1200/800"
    ],
    [
        "id" => 3, "category" => "studio", "title" => "Pro Lighting Setup",
        "caption" => "Home studio floor plan with double softbox diffusers and boom arm.",
        "image" => "https://picsum.photos/id/250/1200/800"
    ],
    [
        "id" => 4, "category" => "portraits", "title" => "Minimalist Headshot",
        "caption" => "High-key studio lighting with seamless white backdrop.",
        "image" => "https://picsum.photos/id/64/1200/800"
    ],
    [
        "id" => 5, "category" => "products", "title" => "Skincare Brand Ad",
        "caption" => "E-commerce flat-lay presentation with natural diffusion fill.",
        "image" => "https://picsum.photos/id/1059/1200/800"
    ],
    [
        "id" => 6, "category" => "studio", "title" => "RGB Ambient Rig",
        "caption" => "Customizable mood lighting configurations for video podcasts and streams.",
        "image" => "https://picsum.photos/id/445/1200/800"
    ]
];

// Backend Processing & Fetching Reserved Dates
$message = "";
$message_type = "";
$booked_dates = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch existing booked dates
    $stmt = $pdo->query("SELECT booking_date FROM bookings");
    $booked_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (\PDOException $e) {
    // Fallback demo dates if DB isn't connected yet
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
            // Check double booking server-side
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
            $check_stmt->execute([$booking_date]);

            if ($check_stmt->fetchColumn() > 0) {
                $message = "The selected date ($booking_date) was just reserved. Please pick another date.";
                $message_type = "error";
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO bookings (client_name, client_email, service_type, booking_date, notes) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$client_name, $client_email, $service_type, $booking_date, $notes]);
                
                $message = "Session successfully booked for $booking_date!";
                $message_type = "success";
                $booked_dates[] = $booking_date;
            }
        } else {
            $message = "Demo mode: Form submitted for $booking_date (Database not connected).";
            $message_type = "success";
            $booked_dates[] = $booking_date;
        }
    } else {
        $message = "Please fill in all required fields accurately.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $studio_name ?> | <?= $tagline ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: #121929;
            --accent-gold: #f59e0b;
            --accent-blue: #2563eb;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-line: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            line-height: 1.6;
        }

        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(9, 13, 22, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-line);
            z-index: 1000;
        }

        nav {
            max-width: 1200px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #fff;
        }

        .logo span {
            background: linear-gradient(135deg, var(--accent-gold), #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        nav a:hover {
            color: var(--accent-gold);
        }

        section {
            max-width: 1200px;
            margin: auto;
            padding: 100px 30px 60px;
        }

        /* HERO SECTION */
        #hero {
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            padding-top: 140px;
            max-width: 100%;
            margin: 0;
            padding-left: 30px;
            padding-right: 30px;
            background: linear-gradient(rgba(9, 13, 22, 0.5), rgba(9, 13, 22, 0.6)), 
                        url('https://images.unsplash.com/photo-1537904904737-13fc7e91a072?w=1600&h=900&fit=crop') center/cover no-repeat fixed;
            background-attachment: fixed;
            position: relative;
            overflow: hidden;
        }

        .hero-text {
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .hero-text h1 {
            font-size: clamp(38px, 6vw, 62px);
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, var(--accent-gold), #ff6b6b, #4f46e5);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 8s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero-text p {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-gold), #ff6b6b);
            color: #000;
            font-weight: 700;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.5), 0 0 20px rgba(255, 107, 107, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #fff;
            border: 1px solid var(--border-line);
            margin-left: 10px;
        }

        .btn-secondary:hover {
            border-color: var(--accent-gold);
            color: var(--accent-gold);
        }

        /* SECTION HEADINGS */
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 36px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--accent-gold), #ff6b6b, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(245, 158, 11, 0.2);
        }

        .section-title p {
            background: linear-gradient(135deg, var(--text-muted), var(--accent-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* SERVICES GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            border-radius: 12px;
            padding: 30px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            border-color: var(--accent-gold);
        }

        .card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: var(--accent-gold);
            background: linear-gradient(135deg, var(--accent-gold), #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card p {
            color: #b4c0db;
            line-height: 1.8;
        }

        .price-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(255, 107, 107, 0.2));
            color: var(--accent-gold);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            border: 1px solid rgba(245, 158, 11, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* GALLERY SECTION */
        .filter-bar {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            color: var(--text-muted);
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--accent-gold);
            color: #000;
            border-color: var(--accent-gold);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            cursor: pointer;
            aspect-ratio: 4/3;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.08);
        }

        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(9, 13, 22, 0.95), rgba(9, 13, 22, 0.2));
            opacity: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 20px;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-overlay h4 {
            font-size: 20px;
            background: linear-gradient(135deg, #fff, var(--accent-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gallery-overlay p {
            font-size: 14px;
            color: #d1d5db;
            margin-top: 5px;
        }

        /* LIGHTBOX POPUP MODAL */
        .lightbox-modal {
            position: fixed;
            inset: 0;
            background: rgba(9, 13, 22, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .lightbox-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .lightbox-content {
            background: rgba(18, 25, 41, 0.9);
            border: 1px solid var(--border-line);
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            position: relative;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
        }

        .lightbox-img-wrapper {
            position: relative;
            width: 100%;
            max-height: 550px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-img-wrapper img {
            max-width: 100%;
            max-height: 550px;
            object-fit: contain;
        }

        .lightbox-meta {
            padding: 20px 25px;
            background: var(--bg-card);
        }

        .lightbox-meta h3 {
            font-size: 22px;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--accent-gold), #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .lightbox-meta p {
            color: #b4c0db;
            font-size: 15px;
            line-height: 1.6;
        }

        .lightbox-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            color: #fff;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 10;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: 1px solid var(--border-line);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .lightbox-nav:hover {
            background: var(--accent-gold);
            color: #000;
        }

        .lightbox-prev { left: 15px; }
        .lightbox-next { right: 15px; }

        /* BOOKING FORM */
        .form-container {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            padding: 40px;
            border-radius: 16px;
            max-width: 700px;
            margin: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            background: linear-gradient(135deg, var(--accent-gold), #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px;
            background: #0b101d;
            border: 1px solid var(--border-line);
            border-radius: 8px;
            color: white;
            font-size: 16px;
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
        }

        input::placeholder, textarea::placeholder {
            color: #6b7280;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #f87171;
        }

        footer {
            border-top: 1px solid var(--border-line);
            padding: 30px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            #hero {
                flex-direction: column;
                text-align: center;
                background-attachment: scroll;
                padding-top: 100px;
            }

            .btn-secondary {
                margin-left: 0;
                margin-top: 10px;
            }

            nav ul {
                display: none;
            }
        }
    </style>
</head>
<body>

<header>
    <nav>
        <div class="logo"><?= $studio_name ?><span>.</span></div>
        <ul>
            <li><a href="#hero">Home</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#gallery">Showcase</a></li>
            <li><a href="#booking">Book Studio</a></li>
        </ul>
    </nav>
</header>

<section id="hero">
    <div class="hero-text">
        <h1>Capture Your Best Moments At Home</h1>
        <p><?= $tagline ?>. Equipped with studio lighting, professional backdrops, and high-resolution camera gear.</p>
        <a href="#booking" class="btn btn-primary">Book Studio Session</a>
        <a href="#gallery" class="btn btn-secondary">View Showcase</a>
    </div>
</section>

<section id="services">
    <div class="section-title">
        <h2>Our Offerings</h2>
        <p style="color: var(--text-muted);">Choose a service or rent the studio space directly</p>
    </div>

    <div class="grid">
        <?php foreach ($services as $s): ?>
            <div class="card">
                <h3><?= htmlspecialchars($s["title"]) ?></h3>
                <span class="price-badge"><?= htmlspecialchars($s["price"]) ?></span>
                <p style="color: var(--text-muted);"><?= htmlspecialchars($s["description"]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="gallery">
    <div class="section-title">
        <h2>Studio Showcase</h2>
        <p style="color: var(--text-muted);">Explore sample photos and studio configurations</p>
    </div>

    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All Items</button>
        <button class="filter-btn" data-filter="portraits">Portraits</button>
        <button class="filter-btn" data-filter="products">Product Ads</button>
        <button class="filter-btn" data-filter="studio">Studio Setup</button>
    </div>

    <div class="gallery-grid">
        <?php foreach ($gallery_items as $index => $item): ?>
            <div class="gallery-item" 
                 data-category="<?= $item['category'] ?>" 
                 data-index="<?= $index ?>"
                 data-title="<?= htmlspecialchars($item['title']) ?>"
                 data-caption="<?= htmlspecialchars($item['caption']) ?>"
                 data-src="<?= $item['image'] ?>">
                <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                <div class="gallery-overlay">
                    <h4><?= htmlspecialchars($item['title']) ?></h4>
                    <p><?= htmlspecialchars($item['caption']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="booking">
    <div class="section-title">
        <h2>Book A Session</h2>
        <p style="color: var(--text-muted);">Reserve studio time (Unavailable dates are grayed out automatically)</p>
    </div>

    <div class="form-container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="#booking">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Your name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="yourname@example.com" required>
            </div>

            <div class="form-group">
                <label>Select Service</label>
                <select name="service">
                    <option>Portrait & Headshots</option>
                    <option>Product Photography</option>
                    <option>Studio Space Rental</option>
                </select>
            </div>

            <div class="form-group">
                <label>Select Date</label>
                <input type="text" id="datepicker" name="date" placeholder="Click to choose an available date..." required readonly>
            </div>

            <div class="form-group">
                <label>Additional Notes / Details</label>
                <textarea name="notes" rows="4" placeholder="Tell us about your shoot requirements..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Confirm Reservation</button>
        </form>
    </div>
</section>

<div class="lightbox-modal" id="lightbox">
    <div class="lightbox-content">
        <button class="lightbox-close" id="lightboxClose">&times;</button>
        <button class="lightbox-nav lightbox-prev" id="lightboxPrev">&#10094;</button>
        <button class="lightbox-nav lightbox-next" id="lightboxNext">&#10095;</button>
        
        <div class="lightbox-img-wrapper">
            <img id="lightboxImg" src="" alt="Gallery Image">
        </div>
        
        <div class="lightbox-meta">
            <h3 id="lightboxTitle"></h3>
            <p id="lightboxCaption"></p>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> <?= $studio_name ?>. Located in <?= $location ?>. Contact: <?= $phone ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Double-Booking Prevention Datepicker
    const unavailableDates = <?= json_encode($booked_dates) ?>;

    flatpickr("#datepicker", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: unavailableDates,
        locale: {
            firstDayOfWeek: 1
        }
    });

    // 2. Gallery Filter Logic
    const filterBtns = document.querySelectorAll(".filter-btn");
    const galleryItems = document.querySelectorAll(".gallery-item");

    filterBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            filterBtns.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const filter = btn.dataset.filter;

            galleryItems.forEach(item => {
                if (filter === "all" || item.dataset.category === filter) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }
            });
        });
    });

    // 3. Lightbox Gallery Popup Logic
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightboxImg");
    const lightboxTitle = document.getElementById("lightboxTitle");
    const lightboxCaption = document.getElementById("lightboxCaption");
    const closeBtn = document.getElementById("lightboxClose");
    const prevBtn = document.getElementById("lightboxPrev");
    const nextBtn = document.getElementById("lightboxNext");

    let visibleItems = [];
    let currentIndex = 0;

    function updateVisibleItems() {
        visibleItems = Array.from(galleryItems).filter(item => item.style.display !== "none");
    }

    function openLightbox(element) {
        updateVisibleItems();
        currentIndex = visibleItems.indexOf(element);
        showImage(currentIndex);
        lightbox.classList.add("active");
    }

    function showImage(index) {
        if (visibleItems.length === 0) return;
        const item = visibleItems[index];
        lightboxImg.src = item.dataset.src;
        lightboxTitle.textContent = item.dataset.title;
        lightboxCaption.textContent = item.dataset.caption;
    }

    galleryItems.forEach(item => {
        item.addEventListener("click", () => openLightbox(item));
    });

    closeBtn.addEventListener("click", () => {
        lightbox.classList.remove("active");
    });

    lightbox.addEventListener("click", (e) => {
        if (e.target === lightbox) lightbox.classList.remove("active");
    });

    prevBtn.addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + visibleItems.length) % visibleItems.length;
        showImage(currentIndex);
    });

    nextBtn.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % visibleItems.length;
        showImage(currentIndex);
    });

    document.addEventListener("keydown", (e) => {
        if (!lightbox.classList.contains("active")) return;
        if (e.key === "Escape") lightbox.classList.remove("active");
        if (e.key === "ArrowLeft") prevBtn.click();
        if (e.key === "ArrowRight") nextBtn.click();
    });
});
</script>

</body>
</html>
