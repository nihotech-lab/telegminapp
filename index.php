<?php

// ===============================
// PORTFOLIO CONFIGURATION DATA
// ===============================

$name = "Nihomo";
$role = "Python & Flutter Developer";
$tagline = "Building modern web applications, high-performance mobile apps, and intelligent AI solutions.";

$skills = [
    ["name" => "Python", "level" => "Advanced", "category" => "Backend & AI"],
    ["name" => "Flutter & Dart", "level" => "Advanced", "category" => "Mobile"],
    ["name" => "PHP & MySQL", "level" => "Intermediate", "category" => "Backend"],
    ["name" => "HTML5 & CSS3", "level" => "Advanced", "category" => "Frontend"],
    ["name" => "Artificial Intelligence", "level" => "Intermediate", "category" => "AI"],
    ["name" => "REST APIs", "level" => "Advanced", "category" => "Backend"]
];

$projects = [
    [
        "id" => "proj-1",
        "title" => "Cross-Platform Flutter App",
        "category" => "mobile",
        "badge" => "Mobile App",
        "description" => "A sleek mobile application engineered with Flutter, Provider, and Firebase real-time database integrations.",
        "long_description" => "Features real-time data sync, state management using Provider/Riverpod, offline caching, and responsive UI design supporting both iOS and Android.",
        "tech" => ["Flutter", "Dart", "Firebase", "REST API"]
    ],
    [
        "id" => "proj-2",
        "title" => "AI-Powered Data Pipeline",
        "category" => "ai",
        "badge" => "Python & AI",
        "description" => "An intelligent automation script and machine learning pipeline for automated text parsing and predictions.",
        "long_description" => "Processes raw input streams using Python, extracts natural language entities, and delivers predictive analytics through a lightweight API service.",
        "tech" => ["Python", "OpenCV", "Pandas", "FastAPI"]
    ],
    [
        "id" => "proj-3",
        "title" => "Dynamic PHP Web Platform",
        "category" => "web",
        "badge" => "Web Application",
        "description" => "Full-stack web application featuring dynamic content management, secure user auth, and MySQL data modeling.",
        "long_description" => "Designed with modular PHP Architecture, custom routing, relational database structure with optimized queries, and responsive front-end styling.",
        "tech" => ["PHP", "MySQL", "JavaScript", "CSS3"]
    ]
];

$services = [
    [
        "icon" => "⚡",
        "title" => "Mobile Application Development",
        "description" => "Custom native-performance applications for Android and iOS using Flutter and Dart with clean architecture."
    ],
    [
        "icon" => "🤖",
        "title" => "AI & Python Automation",
        "description" => "Automation tools, machine learning pipelines, web scraping tools, and custom backend utilities in Python."
    ],
    [
        "icon" => "🌐",
        "title" => "Full-Stack Web Development",
        "description" => "Dynamic web applications, database management systems, and RESTful APIs built with PHP and MySQL."
    ]
];


// ===============================
// CONTACT FORM PROCESSING
// ===============================

$message_sent = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email     = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $message   = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));

    if ($user_name && $email && $message) {
        // Form processing logic (e.g., mail() or DB insert) can be executed here
        $message_sent = true;
    } else {
        $error_message = "Please fill in all required fields with a valid email address.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($name) ?> | <?= htmlspecialchars($role) ?></title>

    <style>
        /* =====================================
           CORE RESET & CSS VARIABLES
        ===================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --blue: #1677ff;
            --blue-glow: rgba(22, 119, 255, 0.35);
            --red: #ff1744;
            --red-glow: rgba(255, 23, 68, 0.35);
            --dark: #050b18;
            --dark-surface: #0b1428;
            --card: #101a30;
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #ffffff;
            --text-sub: #aab5cc;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--dark);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        section {
            max-width: 1200px;
            margin: auto;
            padding: 100px 30px;
        }

        /* =====================================
           TYPOGRAPHY & GLOBAL STYLES
        ===================================== */
        h1, h2, h3 {
            line-height: 1.2;
            font-weight: 800;
        }

        h2 {
            font-size: clamp(32px, 5vw, 45px);
            margin-bottom: 25px;
        }

        .small-title {
            color: var(--red);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* =====================================
           NAVIGATION
        ===================================== */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(5, 11, 24, 0.85);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--card-border);
        }

        nav {
            max-width: 1200px;
            height: 75px;
            margin: auto;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .logo span {
            color: var(--red);
        }

        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-sub);
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--text-main);
        }

        /* Mobile Hamburger Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 6px;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 1001;
        }

        .menu-toggle span {
            width: 26px;
            height: 2px;
            background: var(--text-main);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        /* =====================================
           HERO SECTION
        ===================================== */
        #home {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            align-items: center;
            gap: 50px;
            position: relative;
            padding-top: 120px;
        }

        #home::before, #home::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            pointer-events: none;
        }

        #home::before {
            width: 380px;
            height: 380px;
            background: var(--blue);
            filter: blur(180px);
            opacity: 0.2;
            left: -100px;
            top: 100px;
            animation: glow 5s infinite alternate ease-in-out;
        }

        #home::after {
            width: 350px;
            height: 350px;
            background: var(--red);
            filter: blur(170px);
            opacity: 0.18;
            right: -100px;
            bottom: 50px;
            animation: glow 6s infinite alternate-reverse ease-in-out;
        }

        .hero-text h1 {
            font-size: clamp(55px, 8vw, 95px);
            background: linear-gradient(90deg, #ffffff, #3d9bff, #ff4d6d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .hero-text h2 {
            font-size: clamp(24px, 4vw, 36px);
            color: var(--text-sub);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .description {
            color: var(--text-sub);
            font-size: 18px;
            max-width: 580px;
            margin-bottom: 35px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue), var(--red));
            color: var(--text-main);
        }

        .btn-secondary {
            border: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-main);
        }

        .btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px var(--red-glow);
        }

        /* Hero Avatar Widget */
        .profile-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile {
            width: 280px;
            height: 280px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--dark);
            border: 4px solid transparent;
            background: linear-gradient(var(--dark), var(--dark)) padding-box,
                        linear-gradient(135deg, var(--blue), var(--red)) border-box;
            box-shadow: 0 0 50px var(--blue-glow);
            animation: floating 4s ease-in-out infinite;
            text-align: center;
        }

        .profile-icon {
            font-size: 50px;
            margin-bottom: 8px;
        }

        .profile-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-main);
        }

        /* =====================================
           SECTION: ABOUT
        ===================================== */
        #about {
            background: var(--dark-surface);
            max-width: none;
            padding-left: max(30px, calc((100% - 1140px)/2));
            padding-right: max(30px, calc((100% - 1140px)/2));
        }

        .about-content {
            max-width: 800px;
        }

        .about-content p {
            color: var(--text-sub);
            font-size: 18px;
            margin-bottom: 20px;
        }

        /* =====================================
           SECTION: SKILLS & CARDS
        ===================================== */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .card {
            background: var(--card);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .card:hover {
            transform: translateY(-8px);
            border-color: var(--blue);
            box-shadow: 0 15px 35px var(--blue-glow);
        }

        .card h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .card-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: var(--blue);
            background: rgba(22, 119, 255, 0.12);
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .card p {
            color: var(--text-sub);
            font-size: 15px;
        }

        /* =====================================
           SECTION: PROJECTS & FILTERING
        ===================================== */
        #projects {
            max-width: none;
            background: var(--dark-surface);
            padding-left: max(30px, calc((100% - 1140px)/2));
            padding-right: max(30px, calc((100% - 1140px)/2));
        }

        .filter-container {
            display: flex;
            gap: 12px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-sub);
            padding: 8px 18px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s ease;
        }

        .filter-btn.active, .filter-btn:hover {
            background: var(--blue);
            color: var(--text-main);
            border-color: var(--blue);
        }

        .project-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .tech-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 15px 0 20px;
        }

        .tech-tag {
            font-size: 12px;
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-sub);
            padding: 3px 10px;
            border-radius: 4px;
        }

        /* =====================================
           SECTION: CONTACT FORM
        ===================================== */
        #contact {
            display: grid;
            grid-template-columns: 0.8fr 1.2fr;
            gap: 60px;
            align-items: start;
        }

        .contact-info p {
            color: var(--text-sub);
            font-size: 18px;
            margin-bottom: 25px;
        }

        form {
            background: var(--card);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid var(--card-border);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-sub);
        }

        input, textarea {
            width: 100%;
            padding: 14px 18px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #071022;
            color: var(--text-main);
            outline: none;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(22, 119, 255, 0.2);
        }

        textarea {
            resize: vertical;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(0, 200, 100, 0.15);
            color: #55ff99;
            border: 1px solid rgba(0, 200, 100, 0.3);
        }

        .alert-error {
            background: rgba(255, 23, 68, 0.15);
            color: #ff6b81;
            border: 1px solid rgba(255, 23, 68, 0.3);
        }

        /* =====================================
           MODAL COMPONENT
        ===================================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            padding: 35px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            animation: modalFadeIn 0.3s ease;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: var(--text-sub);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: var(--text-main);
        }

        /* =====================================
           FOOTER
        ===================================== */
        footer {
            padding: 35px 20px;
            text-align: center;
            color: var(--text-sub);
            font-size: 14px;
            background: #030711;
            border-top: 1px solid var(--card-border);
        }

        /* =====================================
           ANIMATIONS & MEDIA QUERIES
        ===================================== */
        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        @keyframes glow {
            from { transform: scale(0.9); }
            to { transform: scale(1.15); }
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            #home {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 40px;
            }

            .description {
                margin-left: auto;
                margin-right: auto;
            }

            .btn-group {
                justify-content: center;
            }

            #contact {
                grid-template-columns: 1fr;
            }

            .menu-toggle {
                display: flex;
            }

            .nav-links {
                position: fixed;
                top: 75px;
                right: -100%;
                width: 100%;
                height: calc(100vh - 75px);
                background: var(--dark);
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 35px;
                transition: right 0.4s ease;
            }

            .nav-links.active {
                right: 0;
            }
        }
    </style>
</head>
<body>

<!-- =====================================
     NAVIGATION HEADER
===================================== -->
<header>
    <nav>
        <div class="logo">
            <a href="#home"><?= htmlspecialchars($name) ?><span>.</span></a>
        </div>

        <button class="menu-toggle" id="menuToggle" aria-label="Toggle Navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#skills">Skills</a></li>
            <li><a href="#projects">Projects</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
    </nav>
</header>

<!-- =====================================
     HERO SECTION
===================================== -->
<section id="home">
    <div class="hero-text">
        <p class="small-title">Hello, World!</p>
        <h1><?= htmlspecialchars($name) ?></h1>
        <h2><?= htmlspecialchars($role) ?></h2>
        <p class="description"><?= htmlspecialchars($tagline) ?></p>

        <div class="btn-group">
            <a href="#projects" class="btn btn-primary">Explore Work</a>
            <a href="#contact" class="btn btn-secondary">Get In Touch</a>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile">
            <div class="profile-icon">💻</div>
            <div class="profile-title">Software Engineer</div>
        </div>
    </div>
</section>

<!-- =====================================
     ABOUT SECTION
===================================== -->
<section id="about">
    <div class="about-content">
        <p class="small-title">About Me</p>
        <h2>Engineering Code into Practical Solutions</h2>
        <p>I am a developer focused on modern web architectures, cross-platform mobile app ecosystems, and intelligent automation systems.</p>
        <p>My goal is to translate technical requirements into clean, scalable, and responsive software tools that deliver great user experiences.</p>
    </div>
</section>

<!-- =====================================
     SKILLS SECTION
===================================== -->
<section id="skills">
    <p class="small-title">Core Competencies</p>
    <h2>Skills & Tooling</h2>

    <div class="grid">
        <?php foreach ($skills as $skill): ?>
            <div class="card">
                <span class="card-badge"><?= htmlspecialchars($skill["category"]) ?></span>
                <h3><?= htmlspecialchars($skill["name"]) ?></h3>
                <p>Proficiency: <?= htmlspecialchars($skill["level"]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- =====================================
     PROJECTS SECTION (FILTERABLE)
===================================== -->
<section id="projects">
    <p class="small-title">Portfolio</p>
    <h2>Featured Projects</h2>

    <div class="filter-container">
        <button class="filter-btn active" data-filter="all">All Projects</button>
        <button class="filter-btn" data-filter="mobile">Mobile</button>
        <button class="filter-btn" data-filter="ai">AI / Python</button>
        <button class="filter-btn" data-filter="web">Web Development</button>
    </div>

    <div class="grid" id="projectGrid">
        <?php foreach ($projects as $project): ?>
            <div class="card project-card" data-category="<?= $project["category"] ?>">
                <div>
                    <span class="card-badge"><?= htmlspecialchars($project["badge"]) ?></span>
                    <h3><?= htmlspecialchars($project["title"]) ?></h3>
                    <p><?= htmlspecialchars($project["description"]) ?></p>
                    
                    <div class="tech-tags">
                        <?php foreach ($project["tech"] as $tech): ?>
                            <span class="tech-tag"><?= htmlspecialchars($tech) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button 
                    class="btn btn-secondary open-modal-btn" 
                    style="width: 100%; margin-top: 15px;"
                    data-title="<?= htmlspecialchars($project["title"]) ?>"
                    data-desc="<?= htmlspecialchars($project["long_description"]) ?>"
                    data-tech="<?= htmlspecialchars(implode(', ', $project["tech"])) ?>">
                    View Details
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- =====================================
     SERVICES SECTION
===================================== -->
<section id="services">
    <p class="small-title">Services</p>
    <h2>What I Build</h2>

    <div class="grid">
        <?php foreach ($services as $service): ?>
            <div class="card">
                <div style="font-size: 32px; margin-bottom: 12px;"><?= $service["icon"] ?></div>
                <h3><?= htmlspecialchars($service["title"]) ?></h3>
                <p><?= htmlspecialchars($service["description"]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- =====================================
     CONTACT SECTION
===================================== -->
<section id="contact">
    <div class="contact-info">
        <p class="small-title">Contact</p>
        <h2>Let's Connect</h2>
        <p>Have an upcoming project, software idea, or potential collaboration? Send a direct message.</p>
    </div>

    <div>
        <?php if ($message_sent): ?>
            <div class="alert alert-success">
                Thank you! Your message was sent successfully.
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="contactForm">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name" placeholder="John Doe" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="john@example.com" required>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" placeholder="How can I help you?" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Send Message
            </button>
        </form>
    </div>
</section>

<!-- =====================================
     PROJECT MODAL COMPONENT
===================================== -->
<div class="modal" id="projectModal">
    <div class="modal-content">
        <button class="modal-close" id="modalClose">&times;</button>
        <h3 id="modalTitle" style="font-size: 26px; margin-bottom: 10px;">Project Title</h3>
        <p id="modalTech" style="color: var(--blue); font-size: 14px; font-weight: 700; margin-bottom: 15px;"></p>
        <p id="modalDesc" style="color: var(--text-sub); font-size: 16px; line-height: 1.7;"></p>
    </div>
</div>

<!-- =====================================
     FOOTER
===================================== -->
<footer>
    <p>© <?= date("Y") ?> <?= htmlspecialchars($name) ?>. All Rights Reserved.</p>
</footer>

<!-- =====================================
     JAVASCRIPT INTERACTIVITY
===================================== -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // 1. Mobile Menu Toggle
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");

        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("active");
        });

        // Close mobile nav menu on clicking link
        document.querySelectorAll(".nav-links a").forEach(link => {
            link.addEventListener("click", () => {
                navLinks.classList.remove("active");
            });
        });

        // 2. Project Filtering Logic
        const filterBtns = document.querySelectorAll(".filter-btn");
        const projectCards = document.querySelectorAll("#projectGrid .project-card");

        filterBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                filterBtns.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");

                const filter = btn.getAttribute("data-filter");

                projectCards.forEach(card => {
                    if (filter === "all" || card.getAttribute("data-category") === filter) {
                        card.style.display = "flex";
                    } else {
                        card.style.display = "none";
                    }
                });
            });
        });

        // 3. Project Detail Modal Logic
        const modal = document.getElementById("projectModal");
        const modalClose = document.getElementById("modalClose");
        const modalTitle = document.getElementById("modalTitle");
        const modalTech = document.getElementById("modalTech");
        const modalDesc = document.getElementById("modalDesc");

        document.querySelectorAll(".open-modal-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                modalTitle.textContent = btn.getAttribute("data-title");
                modalTech.textContent = "Technologies: " + btn.getAttribute("data-tech");
                modalDesc.textContent = btn.getAttribute("data-desc");
                modal.style.display = "flex";
            });
        });

        modalClose.addEventListener("click", () => {
            modal.style.display = "none";
        });

        window.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });
</script>

</body>
</html>
