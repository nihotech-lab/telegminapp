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

// Each reel carries a poster image AND a preview video clip that autoplays,
// muted and looped, directly in the card.
$reels = [
    [
        "id" => 1, "category" => "wedding", "meta" => "4K · 24FPS · T2.1", "title" => "THE VOW",
        "type" => "Wedding Film",
        "image" => "https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80",
        "video" => "https://assets.mixkit.co/videos/preview/mixkit-bride-and-groom-having-fun-on-a-jetty-40188-large.mp4"
    ],
    [
        "id" => 2, "category" => "event", "meta" => "4K · 24FPS · T2.8", "title" => "GRAND ENTRANCE",
        "type" => "Event Film",
        "image" => "https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80",
        "video" => "https://assets.mixkit.co/videos/preview/mixkit-people-dancing-at-a-party-1230-large.mp4"
    ],
    [
        "id" => 3, "category" => "wedding", "meta" => "4K · 24FPS · T2.1", "title" => "MELS NIGHT",
        "type" => "Cultural Wedding",
        "image" => "https://images.unsplash.com/photo-1583939003579-730e3918a45a?auto=format&fit=crop&w=1200&q=80",
        "video" => "https://assets.mixkit.co/videos/preview/mixkit-traditional-dancers-performing-at-a-festival-4640-large.mp4"
    ],
    [
        "id" => 4, "category" => "portrait", "meta" => "50mm · f/1.2 · ISO 100", "title" => "NIGHT WALK",
        "type" => "Cinematic Portrait",
        "image" => "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1200&q=80",
        "video" => "https://assets.mixkit.co/videos/preview/mixkit-portrait-of-a-woman-walking-at-night-34561-large.mp4"
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">

    <style>
        :root {
            --bg-black: #050505;
            --bg-card: #0d0d0d;
            --bg-card-hover: #121212;
            --accent-gold: #e2b13c;
            --accent-gold-soft: rgba(226, 177, 60, 0.15);
            --text-main: #f4f4f4;
            --text-muted: #8a8a8a;
            --border-line: rgba(255, 255, 255, 0.10);
            --border-line-strong: rgba(255, 255, 255, 0.22);
            --font-display: 'Bebas Neue', 'Inter', sans-serif;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: 'JetBrains Mono', 'Courier New', Courier, monospace;
            --ease-smooth: cubic-bezier(0.16, 1, 0.3, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            background-color: var(--bg-black);
            color: var(--text-main);
            font-family: var(--font-body);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        ::selection { background: var(--accent-gold); color: #000; }

        /* subtle full-page grain for a filmic finish */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            opacity: 0.035;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* HEADER */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(5, 5, 5, 0.7);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-line);
            z-index: 1000;
            transition: background 0.4s var(--ease-smooth), border-color 0.4s var(--ease-smooth), padding 0.4s var(--ease-smooth);
        }

        header.scrolled {
            background: rgba(5, 5, 5, 0.92);
            border-color: var(--border-line-strong);
        }

        nav {
            max-width: 1320px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 30px;
            transition: padding 0.4s var(--ease-smooth);
        }

        header.scrolled nav { padding: 15px 30px; }

        .logo {
            font-family: var(--font-display);
            font-size: 24px;
            letter-spacing: 3px;
            color: #fff;
            text-transform: uppercase;
        }

        .logo span { color: var(--accent-gold); }

        nav ul {
            display: flex;
            list-style: none;
            gap: 34px;
        }

        nav a {
            position: relative;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: color 0.3s;
            padding-bottom: 4px;
        }

        nav a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 1px;
            background: var(--accent-gold);
            transition: width 0.35s var(--ease-smooth);
        }

        nav a:hover { color: #fff; }
        nav a:hover::after { width: 100%; }

        .nav-cta {
            display: none;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
        }

        .menu-toggle span {
            width: 24px;
            height: 2px;
            background: #fff;
            transition: transform 0.3s var(--ease-smooth), opacity 0.3s;
        }

        /* HERO */
        #hero-wrapper {
            position: relative;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: flex-end;
            padding-bottom: 90px;
            overflow: hidden;
        }

        #hero-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            display: block;
            cursor: grab;
        }

        .hero-vignette {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background: radial-gradient(ellipse at center, transparent 40%, rgba(5,5,5,0.55) 100%);
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(5, 5, 5, 0.05) 0%,
                rgba(5, 5, 5, 0.55) 65%,
                rgba(5, 5, 5, 1) 100%
            );
            z-index: 2;
            pointer-events: none;
        }

        #hero {
            position: relative;
            z-index: 3;
            max-width: 1320px;
            margin: auto;
            width: 100%;
            padding: 0 30px;
            pointer-events: none;
        }

        #hero a { pointer-events: auto; }

        .hero-text {
            animation: fadeInUp 1.2s var(--ease-smooth) forwards;
            opacity: 0;
            animation-delay: 0.15s;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .lens-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--accent-gold);
            letter-spacing: 3px;
            margin-bottom: 18px;
            text-transform: uppercase;
        }

        .lens-tag .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #ff4d4d;
            animation: recPulse 1.4s ease-in-out infinite;
        }

        @keyframes recPulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(255,77,77,0.5); }
            50% { opacity: 0.3; box-shadow: 0 0 0 5px rgba(255,77,77,0); }
        }

        .hero-text h1 {
            font-family: var(--font-display);
            font-size: clamp(52px, 9vw, 108px);
            font-weight: 400;
            line-height: 0.92;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .hero-text p {
            max-width: 560px;
            font-size: 18px;
            font-weight: 400;
            color: #cfd2d6;
            margin-bottom: 38px;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--accent-gold);
            color: #000;
            padding: 17px 34px;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            border-radius: 2px;
            transition: transform 0.4s var(--ease-smooth), background 0.4s, box-shadow 0.4s;
            box-shadow: 0 0 0 rgba(226,177,60,0);
        }

        .hero-cta:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 14px 34px rgba(226,177,60,0.25);
        }

        .hero-cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-left: 18px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            border-bottom: 1px solid var(--border-line-strong);
            padding-bottom: 4px;
            transition: border-color 0.3s, color 0.3s;
        }

        .hero-cta-secondary:hover { color: var(--accent-gold); border-color: var(--accent-gold); }

        .scroll-cue {
            position: absolute;
            bottom: 26px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-family: var(--font-mono);
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .scroll-cue .line {
            width: 1px;
            height: 34px;
            background: linear-gradient(to bottom, var(--accent-gold), transparent);
            animation: scrollDown 1.8s ease-in-out infinite;
        }

        @keyframes scrollDown {
            0% { transform: scaleY(0); transform-origin: top; opacity: 1; }
            50% { transform: scaleY(1); transform-origin: top; opacity: 1; }
            51% { transform-origin: bottom; }
            100% { transform: scaleY(0); transform-origin: bottom; opacity: 0.4; }
        }

        /* MARQUEE */
        .marquee-container {
            position: relative;
            z-index: 4;
            width: 100%;
            overflow: hidden;
            background: var(--bg-card);
            border-top: 1px solid var(--border-line);
            border-bottom: 1px solid var(--border-line);
            padding: 18px 0;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-block;
            animation: marquee 28s linear infinite;
        }

        .marquee-content span {
            font-family: var(--font-mono);
            font-size: 13px;
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
            max-width: 1320px;
            margin: auto;
            padding: 130px 30px;
        }

        .reveal {
            opacity: 0;
            transform: translateY(36px);
            transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth);
        }

        .reveal.is-visible { opacity: 1; transform: translateY(0); }

        .section-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 64px;
            border-bottom: 1px solid var(--border-line);
            padding-bottom: 22px;
        }

        .section-hdr h2 {
            font-family: var(--font-display);
            font-size: 44px;
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .section-hdr p {
            font-family: var(--font-mono);
            color: var(--accent-gold);
            font-size: 12px;
            letter-spacing: 1px;
        }

        /* REELS */
        .reels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 42px;
        }

        .reel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-line);
            overflow: hidden;
            cursor: pointer;
            position: relative;
            border-radius: 3px;
            transition: border-color 0.4s var(--ease-smooth), transform 0.5s var(--ease-smooth), box-shadow 0.5s var(--ease-smooth);
        }

        .reel-card:hover {
            border-color: var(--accent-gold);
            transform: translateY(-6px);
            box-shadow: 0 24px 48px rgba(0,0,0,0.45);
        }

        .reel-media {
            position: relative;
            width: 100%;
            height: 480px;
            overflow: hidden;
            background: #000;
        }

        .reel-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(30%);
            transition: filter 0.6s var(--ease-smooth), transform 0.7s var(--ease-smooth);
        }

        .reel-card:hover .reel-video {
            filter: grayscale(0%);
            transform: scale(1.05);
        }

        .reel-meta {
            padding: 26px;
            position: relative;
            z-index: 4;
        }

        .reel-meta .cam-specs {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--accent-gold);
            margin-bottom: 10px;
        }

        .reel-meta h3 {
            font-family: var(--font-display);
            font-size: 26px;
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
            padding: 42px;
            border-radius: 3px;
            transition: border-color 0.4s var(--ease-smooth), transform 0.4s var(--ease-smooth), background 0.4s;
        }

        .service-box:hover {
            border-color: var(--accent-gold);
            background: var(--bg-card-hover);
            transform: translateY(-4px);
        }

        .service-box .badge {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--accent-gold);
            margin-bottom: 22px;
            display: block;
        }

        .service-box h3 {
            font-family: var(--font-display);
            font-size: 26px;
            margin-bottom: 14px;
            letter-spacing: 0.5px;
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
            max-width: 820px;
            margin: auto;
            border-radius: 3px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            background: #000;
            border: 1px solid var(--border-line);
            color: #fff;
            font-size: 15px;
            font-family: var(--font-body);
            outline: none;
            border-radius: 2px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(226,177,60,0.12);
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
            border-radius: 2px;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s var(--ease-smooth);
        }

        .btn-submit:hover { background: #fff; transform: translateY(-2px); }

        .alert {
            padding: 15px;
            font-family: var(--font-mono);
            font-size: 13px;
            margin-bottom: 25px;
            text-align: center;
            border-radius: 2px;
        }
        .alert-success { background: rgba(226, 177, 60, 0.15); color: var(--accent-gold); border: 1px solid var(--accent-gold); }
        .alert-error { background: rgba(255, 0, 0, 0.15); color: #ff5555; border: 1px solid #ff5555; }

        /* WHATSAPP */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #25d366;
            color: #fff;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            z-index: 1000;
            text-decoration: none;
            transition: transform 0.35s var(--ease-smooth), box-shadow 0.35s;
        }

        .whatsapp-float:hover { transform: scale(1.1); box-shadow: 0 14px 32px rgba(0,0,0,0.6); }

        footer {
            border-top: 1px solid var(--border-line);
            padding: 60px 30px 40px;
            text-align: center;
        }

        footer .footer-logo {
            font-family: var(--font-display);
            font-size: 22px;
            letter-spacing: 3px;
            margin-bottom: 12px;
        }

        footer .footer-logo span { color: var(--accent-gold); }

        footer p {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-muted);
        }

        @media (max-width: 900px) {
            nav ul { display: none; }
            .menu-toggle { display: flex; }
            .form-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .reels-grid { grid-template-columns: 1fr; }
            .booking-wrapper { padding: 30px; }
            section { padding: 90px 22px; }
            .section-hdr { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

<header id="site-header">
    <nav>
        <div class="logo"><?= $studio_name ?><span>.</span></div>
        <ul>
            <li><a href="#hero-wrapper">Film</a></li>
            <li><a href="#reels">Reels</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#booking">Reserve</a></li>
        </ul>
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </nav>
</header>

<div id="hero-wrapper">
    <canvas id="hero-canvas"></canvas>
    <div class="hero-vignette"></div>
    <div class="hero-overlay"></div>

    <div id="hero">
        <div class="hero-text">
            <div class="lens-tag"><span class="dot"></span>NOW SHOWING · FEATURED FILM STUDIO</div>
            <h1>YOUR DAY.<br>IN MOTION.</h1>
            <p>We don't just record the day — we direct it, light it, and cut it like cinema. Based at Bole Medhanialem, working across Ethiopia.</p>
            <a href="#booking" class="hero-cta">Book Date</a>
            <a href="#reels" class="hero-cta-secondary">Watch Reels</a>
        </div>
    </div>

    <div class="scroll-cue"><span>Scroll</span><span class="line"></span></div>
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
    <div class="section-hdr reveal">
        <h2>Recent Reels</h2>
        <p>DIRECTED & CUT BY AD PICTURES</p>
    </div>

    <div class="reels-grid">
        <?php foreach ($reels as $r): ?>
            <div class="reel-card reveal">
                <div class="reel-media">
                    <video class="reel-video" src="<?= $r['video'] ?>" poster="<?= $r['image'] ?>" autoplay muted loop playsinline preload="metadata"></video>
                </div>
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
    <div class="section-hdr reveal">
        <h2>Services</h2>
        <p>PRODUCTION PACKAGES</p>
    </div>

    <div class="services-grid">
        <?php foreach ($services as $s): ?>
            <div class="service-box reveal">
                <span class="badge"><?= $s['badge'] ?></span>
                <h3><?= $s['title'] ?></h3>
                <p><?= $s['description'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="booking">
    <div class="section-hdr reveal">
        <h2>Reserve Shoot</h2>
        <p>DOUBLE-BOOKING GUARD ACTIVE</p>
    </div>

    <div class="booking-wrapper reveal">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" action="#booking">
            <div class="form-row">
                <div class="form-group">
                    <label>FULL NAME</label>
                    <input type="text" name="name" placeholder="Name" required>
                </div>

                <div class="form-group">
                    <label>EMAIL ADDRESS</label>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
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
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984 0 1.758.459 3.474 1.33 4.988l-1.416 5.171 5.293-1.389c1.458.796 3.102 1.216 4.78 1.217h.004c5.507 0 9.99-4.478 9.99-9.985 0-2.667-1.037-5.176-2.922-7.062-1.886-1.886-4.394-2.924-7.069-2.924zm5.814 14.182c-.252.707-1.473 1.353-2.023 1.411-.518.055-1.196.082-3.418-.838-2.589-1.072-4.228-3.732-4.357-3.905-.129-.173-1.053-1.401-1.053-2.667 0-1.267.662-1.889.897-2.146.235-.257.514-.322.686-.322.172 0 .344.002.493.009.157.007.368-.06.576.438.214.512.729 1.777.793 1.906.064.129.107.279.021.451-.086.172-.129.279-.257.429-.129.15-.271.335-.387.45-.129.129-.264.27-.114.528.15.257.666 1.098 1.428 1.777.98.874 1.806 1.146 2.064 1.275.257.129.408.107.558-.064.15-.172.643-.75.814-1.007.172-.257.343-.214.579-.129.236.086 1.499.707 1.756.836.257.129.429.193.493.301.064.108.064.621-.188 1.328z"/></svg>
</a>

<footer>
    <div class="footer-logo"><?= $studio_name ?><span>.</span></div>
    <p>&copy; <?= date('Y') ?> <?= $studio_name ?> FILM STUDIO · STUDIO: <?= $location ?> · TEL: <?= $phone ?></p>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// ============================================
// HERO: 3D film reel that spins continuously and
// tilts/turns to follow the mouse cursor, set
// inside a drifting gold particle field.
// ============================================
(function () {
    const canvas = document.getElementById('hero-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const heroWrapper = document.getElementById('hero-wrapper');

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 42);

    const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);

    // ---- Lighting: warm key light + cool rim light for a cinematic metal look ----
    const ambient = new THREE.AmbientLight(0x404040, 1.4);
    scene.add(ambient);

    const keyLight = new THREE.PointLight(0xe2b13c, 2.2, 300);
    keyLight.position.set(30, 20, 40);
    scene.add(keyLight);

    const rimLight = new THREE.PointLight(0x6f8bff, 1.1, 300);
    rimLight.position.set(-30, -10, -20);
    scene.add(rimLight);

    const fillLight = new THREE.DirectionalLight(0xffffff, 0.5);
    fillLight.position.set(0, 30, 20);
    scene.add(fillLight);

    // ---- Build a procedural film reel: outer rim, hub, spokes, film strip ----
    const reelGroup = new THREE.Group();

    const goldMaterial = new THREE.MeshStandardMaterial({
        color: 0xe2b13c,
        metalness: 0.85,
        roughness: 0.28,
        emissive: 0x3a2a08,
        emissiveIntensity: 0.15
    });

    const darkMetal = new THREE.MeshStandardMaterial({
        color: 0x1a1a1a,
        metalness: 0.7,
        roughness: 0.4
    });

    // Outer rim
    const rim = new THREE.Mesh(new THREE.TorusGeometry(11, 0.55, 24, 64), goldMaterial);
    reelGroup.add(rim);

    // Inner secondary rim for depth
    const innerRim = new THREE.Mesh(new THREE.TorusGeometry(7.2, 0.3, 20, 56), darkMetal);
    reelGroup.add(innerRim);

    // Center hub
    const hub = new THREE.Mesh(new THREE.CylinderGeometry(2.2, 2.2, 1.4, 32), goldMaterial);
    hub.rotation.x = Math.PI / 2;
    reelGroup.add(hub);

    const hubCap = new THREE.Mesh(new THREE.CylinderGeometry(0.9, 0.9, 1.6, 24), darkMetal);
    hubCap.rotation.x = Math.PI / 2;
    reelGroup.add(hubCap);

    // Spokes connecting hub to inner rim, evenly spaced
    const spokeCount = 6;
    for (let i = 0; i < spokeCount; i++) {
        const angle = (i / spokeCount) * Math.PI * 2;
        const spoke = new THREE.Mesh(new THREE.BoxGeometry(5.6, 0.7, 0.35), goldMaterial);
        spoke.position.set(Math.cos(angle) * 4.9, Math.sin(angle) * 4.9, 0);
        spoke.rotation.z = angle;
        reelGroup.add(spoke);

        // small circular perforations look along the rim (film-reel holes), decorative dots
        const hole = new THREE.Mesh(
            new THREE.TorusGeometry(0.55, 0.12, 8, 16),
            darkMetal
        );
        hole.position.set(Math.cos(angle) * 9.1, Math.sin(angle) * 9.1, 0.3);
        reelGroup.add(hole);
    }

    // A gently curling ribbon of "film strip" arcing off the reel
    const curve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(9, 3, 0),
        new THREE.Vector3(15, 8, 3),
        new THREE.Vector3(20, 2, 7),
        new THREE.Vector3(23, -6, 4),
        new THREE.Vector3(20, -13, -2)
    ]);
    const filmStripGeo = new THREE.TubeGeometry(curve, 60, 0.9, 8, false);
    const filmStripMat = new THREE.MeshStandardMaterial({
        color: 0x2b2b2b,
        metalness: 0.2,
        roughness: 0.7
    });
    const filmStrip = new THREE.Mesh(filmStripGeo, filmStripMat);
    reelGroup.add(filmStrip);

    reelGroup.scale.set(1.15, 1.15, 1.15);
    scene.add(reelGroup);

    // ---- Ambient particle dust for depth behind/around the reel ----
    const PARTICLE_COUNT = 900;
    const positions = new Float32Array(PARTICLE_COUNT * 3);
    for (let i = 0; i < PARTICLE_COUNT; i++) {
        positions[i * 3]     = (Math.random() - 0.5) * 220;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 140;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 160 - 40;
    }
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const material = new THREE.PointsMaterial({
        size: 0.8,
        color: 0xe2b13c,
        transparent: true,
        opacity: 0.55,
        depthWrite: false,
        blending: THREE.AdditiveBlending
    });
    const particles = new THREE.Points(geometry, material);
    scene.add(particles);

    // ---- Mouse tracking: reel turns to "look" toward the cursor ----
    let mouseX = 0, mouseY = 0;
    let targetRotY = 0, targetRotX = 0;

    function onPointerMove(clientX, clientY) {
        const rect = heroWrapper.getBoundingClientRect();
        mouseX = ((clientX - rect.left) / rect.width) * 2 - 1;
        mouseY = ((clientY - rect.top) / rect.height) * 2 - 1;
        targetRotY = mouseX * 0.9;
        targetRotX = -mouseY * 0.5;
    }

    window.addEventListener('mousemove', function (e) { onPointerMove(e.clientX, e.clientY); });
    window.addEventListener('touchmove', function (e) {
        if (e.touches && e.touches[0]) onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
    }, { passive: true });

    function onResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }
    window.addEventListener('resize', onResize);

    const clock = new THREE.Clock();
    let spinRotY = 0;

    function animate() {
        requestAnimationFrame(animate);
        const dt = clock.getDelta();
        const elapsed = clock.getElapsedTime();

        // Continuous spin around its own axis, like a reel actually playing film
        spinRotY += dt * 0.5;

        // Ease toward mouse-driven tilt, layered on top of the constant spin
        reelGroup.rotation.y += ((spinRotY + targetRotY) - reelGroup.rotation.y) * 0.06;
        reelGroup.rotation.x += (targetRotX - reelGroup.rotation.x) * 0.06;

        // Slight bob for life-like drift
        reelGroup.position.y = Math.sin(elapsed * 0.6) * 1.2;

        // Camera drifts subtly with the cursor for parallax depth
        camera.position.x += (mouseX * 6 - camera.position.x) * 0.03;
        camera.position.y += (-mouseY * 4 - camera.position.y) * 0.03;
        camera.lookAt(reelGroup.position);

        particles.rotation.y = elapsed * 0.015;

        renderer.render(scene, camera);
    }
    animate();
})();

// ============================================
// UI polish: header shrink on scroll, mobile menu,
// scroll-reveal for sections/cards, datepicker.
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const unavailableDates = <?= json_encode($booked_dates) ?>;

    flatpickr("#datepicker", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: unavailableDates,
        locale: { firstDayOfWeek: 1 }
    });

    // Header background/size shifts once the page scrolls
    const header = document.getElementById('site-header');
    window.addEventListener('scroll', function () {
        header.classList.toggle('scrolled', window.scrollY > 40);
    });

    // Mobile nav toggle
    const menuToggle = document.getElementById('menuToggle');
    const navList = document.querySelector('nav ul');
    if (menuToggle && navList) {
        menuToggle.addEventListener('click', function () {
            const open = navList.style.display === 'flex';
            navList.style.display = open ? 'none' : 'flex';
            navList.style.cssText += open ? '' : 'position:fixed;top:64px;left:0;right:0;background:rgba(5,5,5,0.97);flex-direction:column;padding:24px 30px;gap:20px;border-bottom:1px solid rgba(255,255,255,0.1);';
        });
    }

    // Scroll-reveal for section headers and cards
    const revealEls = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    revealEls.forEach(function (el) { observer.observe(el); });
});
</script>

</body>
</html>
