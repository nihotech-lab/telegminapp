<?php
// ===============================
// STUDIO DATA & CONFIGURATION
// ===============================

$studio_name = "Studio Vision";
$tagline = "Professional Photo & Video Home Studio";
$phone = "+251 900 000 000";
$location = "Addis Ababa, Ethiopia";

$services = [
    [
        "title" => "Portrait & Headshots",
        "price" => "Br 1,500 / session",
        "description" => "High-end creative portraits with customized backdrop lighting setups."
    ],
    [
        "title" => "Product Photography",
        "price" => "Br 2,000 / batch",
        "description" => "Clean, high-resolution product photography optimized for e-commerce and social ads."
    ],
    [
        "title" => "Content Creator Studio Rental",
        "price" => "Br 800 / hour",
        "description" => "Full studio space access with softboxes, RGB accent lights, ring lights, and background panels."
    ]
];

$message_sent = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client_name = htmlspecialchars($_POST["name"] ?? "");
    $client_email = htmlspecialchars($_POST["email"] ?? "");
    $service_type = htmlspecialchars($_POST["service"] ?? "");
    $booking_date = htmlspecialchars($_POST["date"] ?? "");
    $notes = htmlspecialchars($_POST["notes"] ?? "");

    if (!empty($client_name) && !empty($client_email)) {
        // Here you can handle saving to database or sending an email notification
        $message_sent = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $studio_name ?> | <?= $tagline ?></title>
    
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
            color: var(--accent-gold);
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
        }

        .hero-text {
            flex: 1;
        }

        .hero-text h1 {
            font-size: clamp(38px, 6vw, 62px);
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, var(--accent-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
        }

        .btn-primary {
            background: var(--accent-gold);
            color: #000;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
        }

        /* SERVICES GRID */
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 36px;
            margin-bottom: 10px;
        }

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
        }

        .price-badge {
            display: inline-block;
            background: rgba(245, 158, 11, 0.15);
            color: var(--accent-gold);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }

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
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #34d399;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
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
            <li><a href="#booking">Book Studio</a></li>
        </ul>
    </nav>
</header>

<section id="hero">
    <div class="hero-text">
        <h1>Capture Your Best Moments At Home</h1>
        <p><?= $tagline ?>. Equipped with studio lighting, professional backdrops, and high-resolution camera gear.</p>
        <a href="#booking" class="btn btn-primary">Book Studio Session</a>
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

<section id="booking">
    <div class="section-title">
        <h2>Book A Session</h2>
        <p style="color: var(--text-muted);">Reserve studio time or request custom photoshoot packages</p>
    </div>

    <div class="form-container">
        <?php if ($message_sent): ?>
            <div class="alert-success">
                Reservation request received! We will contact you at <?= $client_email ?> shortly.
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
                <label>Preferred Date</label>
                <input type="date" name="date" required>
            </div>

            <div class="form-group">
                <label>Additional Notes / Details</label>
                <textarea name="notes" rows="4" placeholder="Tell us about your shoot requirements..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; border: none; cursor: pointer; font-size: 16px;">Confirm Reservation</button>
        </form>
    </div>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <?= $studio_name ?>. Located in <?= $location ?>. Contact: <?= $phone ?></p>
</footer>

</body>
</html>
