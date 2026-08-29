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

// AD Pictures Brand Settings
$studio_name = "AD PICTURES";
$tagline     = "Cinematic Film Studio & Stills";
$phone       = "+251 908 030 809";
$whatsapp    = "251908030809";
$location    = "Awlo Business Center, 3rd Floor, Bole Medhanialem, Addis Ababa";

$services = [
    [
        "badge"       => "4K · 24FPS · T2.1 CINEMA LENS",
        "title"       => "Cinematic Wedding Films",
        "price"       => "Full Day Directing",
        "description" => "Directed, lit, and cut like a film — capturing authentic emotion from first light to final dance."
    ],
    [
        "badge"       => "35MM · f/1.4 · HDR STILLS",
        "title"       => "Commercial & Brand Stills",
        "price"       => "Campaign Production",
        "description" => "High-impact editorial imagery and video campaigns crafted to elevate luxury brand identity."
    ],
    [
        "badge"       => "RAW · MULTI-CAM RIG",
        "title"       => "Event & Mels Coverage",
        "price"       => "Multi-Day Package",
        "description" => "High-energy multi-camera event recording utilizing dynamic ambient lighting setups."
    ]
];

$reels = [
    [
        "meta" => "4K · 24FPS · CINEMA SCOPE", "title" => "THE VOW", "type" => "Wedding Film",
        "image" => "https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "meta" => "35MM · HIGH DYNAMIC RANGE", "title" => "GRAND ENTRANCE", "type" => "Event Film",
        "image" => "https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "meta" => "50MM · T1.5 SOFT LIGHTING", "title" => "MELS NIGHT", "type" => "Cultural Film",
        "image" => "https://images.unsplash.com/photo-1583939003579-730e3918a45a?auto=format&fit=crop&w=1200&q=80"
    ]
];

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
                $message = "Date ($booking_date) is reserved. Pick another date.";
                $message_type = "error";
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO bookings (client_name, client_email, service_type, booking_date, notes) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$client_name, $client_email, $service_type, $booking_date, $notes]);
                
                $message = "Session date reserved for $booking_date!";
                $message_type = "success";
                $booked_dates[] = $booking_date;
            }
        } else {
            $message = "Demo Mode: Date $booking_date captured.";
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.min.js"></script>

    <style>
        :root {
            --bg-black: #040404;
            --bg-card: rgba(18, 18, 20, 0.7);
            --accent-gold: #e2b13c;
            --accent-gold-glow: rgba(226, 177, 60, 0.35);
            --text-main: #f5f5f7;
            --text-muted: #999999;
            --border-line: rgba(255, 255, 255, 0.1);
            --font-mono: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-black);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* HEADER */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(4, 4, 4, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-line);
            z-index: 1000;
        }

        nav {
            max-width: 1350px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 30px;
        }

        .logo {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 4px;
            color: #fff;
            text-transform: uppercase;
        }

        .logo span { color: var(--accent-gold); }

        nav ul {
            display: flex;
            list-style: none;
            gap: 35px;
        }

        nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            transition: color 0.3s ease;
        }

        nav a:hover { color: var(--accent-gold); }

        /* HERO WITH 3D INTERACTIVE OBJECT */
        #hero-wrapper {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: radial-gradient(circle at 70% 50%, rgba(226, 177, 60, 0.08) 0%, transparent 60%);
        }

        #three-canvas {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .hero-container {
            position: relative;
            z-index: 2;
            max-width: 1350px;
            margin: auto;
            width: 100%;
            padding: 0 30px;
            pointer-events: auto;
        }

        .hero-text {
            max-width: 650px;
            animation: fadeIn 1.2s ease forwards;
        }

        .lens-tag {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--accent-gold);
            letter-spacing: 4px;
            margin-bottom: 20px;
            text-transform: uppercase;
            display: inline-block;
            background: rgba(226, 177, 60, 0.1);
            padding: 6px 14px;
            border-radius: 4px;
            border: 1px solid rgba(226, 177, 60, 0.2);
        }

        .hero-text h1 {
            font-size: clamp(48px, 7vw, 88px);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -2px;
            text-transform: uppercase;
            margin-bottom: 25px;
            background: linear-gradient(180deg, #ffffff 0%, #a1a1a1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 520px;
        }

        .btn-gold {
            display: inline-block;
            background: var(--accent-gold);
            color: #000;
            padding: 18px 36px;
            font-weight: 800;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            border-radius: 4px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px var(--accent-gold-glow);
        }

        .btn-gold:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 255, 255, 0.3);
        }

        /* MARQUEE TICKER */
        .marquee-container {
            position: relative;
            z-index: 5;
            width: 100%;
            overflow: hidden;
            background: var(--bg-black);
            border-top: 1px solid var(--border-line);
            border-bottom: 1px solid var(--border-line);
            padding: 20px 0;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-block;
            animation: marquee 30s linear infinite;
        }

        .marquee-content span {
            font-family: var(--font-mono);
            font-size: 13px;
            letter-spacing: 4px;
            color: var(--text-muted);
            margin-right: 60px;
            text-transform: uppercase;
        }

        .marquee-content span strong { color: var(--accent-gold); }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* SECTIONS & GRID SYSTEM */
        section {
            max-width: 1350px;
            margin: auto;
            padding: 120px 30px;
        }

        .section-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 60px;
            border-bottom: 1px solid var(--border-line);
            padding-bottom: 25px;
        }

        .section-hdr h2 {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .section-hdr p {
            font-family: var(--font-mono);
            color: var(--accent-gold);
            font-size: 12px;
            letter-spacing: 2px;
        }

        /* REELS / CARDS */
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 35px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            border-radius: 8px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .card:hover {
            border-color: var(--accent-gold);
            transform: translateY(-8px);
        }

        .card-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            filter: grayscale(20%);
            transition: filter 0.5s ease, transform 0.5s ease;
        }

        .card:hover .card-img {
            filter: grayscale(0%);
            transform: scale(1.05);
        }

        .card-body { padding: 30px; }

        .card-meta {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--accent-gold);
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .card-body h3 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        /* BOOKING FORM */
        .booking-box {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            border-radius: 12px;
            padding: 60px;
            max-width: 800px;
            margin: auto;
            backdrop-filter: blur(16px);
        }

        .form-group { margin-bottom: 25px; }

        label {
            display: block;
            font-family: var(--font-mono);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        input, select, textarea {
            width: 100%;
            padding: 16px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid var(--border-line);
            border-radius: 6px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s ease;
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
            padding: 20px;
            width: 100%;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover { background: #fff; }

        .alert {
            padding: 15px;
            font-family: var(--font-mono);
            font-size: 13px;
            margin-bottom: 25px;
            border-radius: 6px;
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
            transition: transform 0.3s ease;
        }

        .whatsapp-float:hover { transform: scale(1.12); }

        footer {
            border-top: 1px solid var(--border-line);
            padding: 50px 30px;
            text-align: center;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-muted);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 992px) {
            #three-canvas { opacity: 0.3; }
            nav ul { display: none; }
            .booking-box { padding: 30px; }
        }
    </style>
</head>
<body>

<header>
    <nav>
        <div class="logo"><?= $studio_name ?><span>.</span></div>
        <ul>
            <li><a href="#hero-wrapper">Studio</a></li>
            <li><a href="#reels">Reels</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#booking">Reserve</a></li>
        </ul>
    </nav>
</header>

<div id="hero-wrapper">
    <div id="three-canvas"></div>

    <div class="hero-container">
        <div class="hero-text">
            <span class="lens-tag">3D CINEMATOGRAPHY EXPERIENCE</span>
            <h1>CRAFTING<br>MOTION PICTURES.</h1>
            <p>We direct, light, and cut your story like high-end cinema. Studio based at Bole Medhanialem, working worldwide.</p>
            <a href="#booking" class="btn-gold">Reserve Production</a>
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
        <h2>FEATURED REELS</h2>
        <p>DIRECTED BY AD PICTURES</p>
    </div>

    <div class="grid-3">
        <?php foreach ($reels as $r): ?>
            <div class="card">
                <img src="<?= $r['image'] ?>" class="card-img" alt="<?= $r['title'] ?>">
                <div class="card-body">
                    <div class="card-meta"><?= $r['meta'] ?></div>
                    <h3><?= $r['title'] ?></h3>
                    <p style="color: var(--text-muted); font-size: 14px;"><?= $r['type'] ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="services">
    <div class="section-hdr">
        <h2>PRODUCTION SERVICES</h2>
        <p>PACKAGES & SPECS</p>
    </div>

    <div class="grid-3">
        <?php foreach ($services as $s): ?>
            <div class="card card-body">
                <div class="card-meta"><?= $s['badge'] ?></div>
                <h3><?= $s['title'] ?></h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 10px;"><?= $s['description'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="booking">
    <div class="section-hdr">
        <h2>RESERVE SHOOT DATE</h2>
        <p>DOUBLE-BOOKING PROTECTION</p>
    </div>

    <div class="booking-box">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" action="#booking">
            <div class="form-group">
                <label>FULL NAME</label>
                <input type="text" name="name" placeholder="Enter full name" required>
            </div>

            <div class="form-group">
                <label>EMAIL ADDRESS</label>
                <input type="email" name="email" placeholder="Enter email address" required>
            </div>

            <div class="form-group">
                <label>PRODUCTION SERVICE</label>
                <select name="service">
                    <option>Cinematic Wedding Films</option>
                    <option>Commercial & Brand Stills</option>
                    <option>Event & Mels Coverage</option>
                </select>
            </div>

            <div class="form-group">
                <label>SHOOT DATE (RESERVED GREYED OUT)</label>
                <input type="text" id="datepicker" name="date" placeholder="Select date..." required readonly>
            </div>

            <div class="form-group">
                <label>PRODUCTION NOTES</label>
                <textarea name="notes" rows="4" placeholder="Venue & production requirements..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Confirm Reservation</button>
        </form>
    </div>
</section>

<a href="https://wa.me/<?= $whatsapp ?>?text=Hello%20AD%20Pictures," class="whatsapp-float" target="_blank">
    <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984 0 1.758.459 3.474 1.33 4.988l-1.416 5.171 5.293-1.389c1.458.796 3.102 1.216 4.78 1.217h.004c5.507 0 9.99-4.478 9.99-9.985 0-2.667-1.037-5.176-2.922-7.062-1.886-1.886-4.394-2.924-7.069-2.924zm5.814 14.182c-.252.707-1.473 1.353-2.023 1.411-.518.055-1.196.082-3.418-.838-2.589-1.072-4.228-3.732-4.357-3.905-.129-.173-1.053-1.401-1.053-2.667 0-1.267.662-1.889.897-2.146.235-.257.514-.322.686-.322.172 0 .344.002.493.009.157.007.368-.06.576.438.214.512.729 1.777.793 1.906.064.129.107.279.021.451-.086.172-.129.279-.257.429-.129.15-.271.335-.387.45-.129.129-.264.27-.114.528.15.257.666 1.098 1.428 1.777.98.874 1.806 1.146 2.064 1.275.257.129.408.107.558-.064.15-.172.643-.75.814-1.007.172-.257.343-.214.579-.129.236.086 1.499.707 1.756.836.257.129.429.193.493.301.064.108.064.621-.188 1.328z"/></svg>
</a>

<footer>
    <p>&copy; <?= date('Y') ?> <?= $studio_name ?> FILM STUDIO · LOCATION: <?= $location ?> · TEL: <?= $phone ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Flatpickr Initializer
    const unavailableDates = <?= json_encode($booked_dates) ?>;
    flatpickr("#datepicker", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: unavailableDates,
        locale: { firstDayOfWeek: 1 }
    });

    // 2. Three.js 3D Cinema Reel Engine Setup
    const container = document.getElementById('three-canvas');
    const scene = new THREE.Scene();

    const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 12;

    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    // Group to hold 3D Reel Assembly
    const filmGroup = new THREE.Group();

    // Metallic Materials
    const goldMaterial = new THREE.MeshStandardMaterial({
        color: 0xe2b13c,
        metalness: 0.9,
        roughness: 0.2
    });

    const darkMetal = new THREE.MeshStandardMaterial({
        color: 0x111113,
        metalness: 0.8,
        roughness: 0.3
    });

    const filmStripMaterial = new THREE.MeshStandardMaterial({
        color: 0x050505,
        roughness: 0.8
    });

    // Outer Film Reel Cylinder Rim
    const outerRimGeo = new THREE.CylinderGeometry(3.5, 3.5, 0.4, 64, 1, true);
    const outerRim = new THREE.Mesh(outerRimGeo, goldMaterial);
    filmGroup.add(outerRim);

    // Inner Core Hub
    const coreGeo = new THREE.CylinderGeometry(1.2, 1.2, 0.42, 32);
    const coreHub = new THREE.Mesh(coreGeo, darkMetal);
    filmGroup.add(coreHub);

    // Film Tape Coil (Middle Layer)
    const tapeGeo = new THREE.CylinderGeometry(3.0, 3.0, 0.38, 64);
    const tape = new THREE.Mesh(tapeGeo, filmStripMaterial);
    filmGroup.add(tape);

    // 6-Spoke Cinema Reel Pattern
    for (let i = 0; i < 6; i++) {
        const spokeGeo = new THREE.BoxGeometry(0.25, 3.4, 0.45);
        const spoke = new THREE.Mesh(spokeGeo, goldMaterial);
        spoke.rotation.z = (Math.PI / 3) * i;
        filmGroup.add(spoke);
    }

    // Position Group in Hero right side
    filmGroup.position.set(window.innerWidth > 992 ? 3.2 : 0, 0, 0);
    scene.add(filmGroup);

    // Lighting Setup
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
    scene.add(ambientLight);

    const pointLight = new THREE.PointLight(0xe2b13c, 2.5, 50);
    pointLight.position.set(5, 5, 8);
    scene.add(pointLight);

    const blueLight = new THREE.PointLight(0x2563eb, 1.2, 50);
    blueLight.position.set(-8, -5, 5);
    scene.add(blueLight);

    // Mouse Cursor Movement Interactivity
    let targetX = 0;
    let targetY = 0;
    let mouseX = 0;
    let mouseY = 0;

    const windowHalfX = window.innerWidth / 2;
    const windowHalfY = window.innerHeight / 2;

    document.addEventListener('mousemove', (event) => {
        mouseX = (event.clientX - windowHalfX);
        mouseY = (event.clientY - windowHalfY);
    });

    // Render Animation Loop
    function animate() {
        requestAnimationFrame(animate);

        // Continuous Smooth Rotation
        filmGroup.rotation.y += 0.008;

        // Smooth Mouse Rotation Tracking (Lerp)
        targetX = mouseX * 0.0008;
        targetY = mouseY * 0.0008;

        filmGroup.rotation.x += (targetY - filmGroup.rotation.x) * 0.05;
        filmGroup.rotation.z += (-targetX - filmGroup.rotation.z) * 0.05;

        renderer.render(scene, camera);
    }

    animate();

    // Window Resize Handler
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        filmGroup.position.set(window.innerWidth > 992 ? 3.2 : 0, 0, 0);
    });
});
</script>

</body>
</html>
