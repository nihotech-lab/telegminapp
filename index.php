```php
<?php

// ===============================
// PHP DATA
// ===============================

$name = "Nihomo";
$role = "Python & Flutter Developer";

$description = "I build modern web applications,
mobile applications and intelligent software solutions.";

$skills = [
    "Python",
    "Flutter",
    "HTML & CSS",
    "PHP",
    "MySQL",
    "Artificial Intelligence"
];

$projects = [
    [
        "title" => "Flutter Mobile App",
        "description" => "Modern Android application built with Flutter and Dart."
    ],
    [
        "title" => "Python AI Project",
        "description" => "Intelligent application developed using Python and AI."
    ],
    [
        "title" => "PHP Web Application",
        "description" => "Dynamic web application using PHP and MySQL."
    ]
];

$services = [
    [
        "title" => "Web Development",
        "description" => "Modern and responsive websites using PHP, HTML and CSS."
    ],
    [
        "title" => "Mobile Development",
        "description" => "Android applications using Flutter and Dart."
    ],
    [
        "title" => "Backend Development",
        "description" => "Backend systems using PHP, Python and MySQL."
    ]
];


// ===============================
// CONTACT FORM
// ===============================

$message_sent = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_name = htmlspecialchars($_POST["name"] ?? "");
    $email = htmlspecialchars($_POST["email"] ?? "");
    $message = htmlspecialchars($_POST["message"] ?? "");

    if (!empty($user_name) && !empty($email) && !empty($message)) {

        // Later we will save this to MySQL.

        $message_sent = true;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= $name ?> | Developer
    </title>

    <style>

        /* =====================================
           RESET
        ===================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, sans-serif;

            background: #050b18;

            color: white;

            line-height: 1.6;

            overflow-x: hidden;
        }


        /* =====================================
           VARIABLES
        ===================================== */

        :root {

            --blue: #1677ff;

            --red: #ff1744;

            --dark: #050b18;

            --card: #101a30;

            --text: #aab5cc;
        }


        /* =====================================
           GLOBAL
        ===================================== */

        a {
            text-decoration: none;
            color: inherit;
        }

        section {

            max-width: 1200px;

            margin: auto;

            padding: 100px 30px;
        }

        h1,
        h2,
        h3 {
            line-height: 1.2;
        }

        h2 {
            font-size: 45px;
            margin-bottom: 25px;
        }


        /* =====================================
           NAVBAR
        ===================================== */

        header {

            position: fixed;

            top: 0;
            left: 0;

            width: 100%;

            z-index: 1000;

            background:
                rgba(5, 11, 24, 0.85);

            backdrop-filter: blur(15px);

            border-bottom:
                1px solid rgba(255,255,255,0.08);
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

            font-size: 28px;

            font-weight: 900;
        }

        .logo span {
            color: var(--red);
        }

        nav ul {

            display: flex;

            gap: 30px;

            list-style: none;
        }

        nav ul a {

            color: var(--text);

            font-weight: 600;

            transition: 0.3s;
        }

        nav ul a:hover {

            color: white;

            color: var(--red);
        }


        /* =====================================
           HERO
        ===================================== */

        #home {

            min-height: 100vh;

            display: grid;

            grid-template-columns:
                1.2fr 0.8fr;

            align-items: center;

            gap: 50px;

            position: relative;
        }

        #home::before {

            content: "";

            position: absolute;

            width: 400px;
            height: 400px;

            background: var(--blue);

            filter: blur(180px);

            opacity: 0.18;

            left: -150px;

            top: 100px;

            z-index: -1;

            animation: glow 5s infinite alternate;
        }

        #home::after {

            content: "";

            position: absolute;

            width: 350px;
            height: 350px;

            background: var(--red);

            filter: blur(170px);

            opacity: 0.15;

            right: -150px;

            bottom: 0;

            z-index: -1;

            animation: glow 6s infinite alternate-reverse;
        }

        .hero-text {

            animation: slideLeft 1s ease;
        }

        .small-title {

            color: var(--red);

            font-size: 16px;

            font-weight: bold;

            letter-spacing: 3px;

            margin-bottom: 10px;
        }

        .hero-text h1 {

            font-size:
                clamp(60px, 9vw, 105px);

            background:
                linear-gradient(
                    90deg,
                    white,
                    #3d9bff,
                    #ff4d6d
                );

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: textGlow 3s infinite alternate;
        }

        .hero-text h2 {

            font-size: 38px;

            margin: 10px 0 20px;
        }

        .description {

            color: var(--text);

            font-size: 18px;

            max-width: 600px;

            margin-bottom: 30px;
        }


        /* =====================================
           BUTTON
        ===================================== */

        .btn {

            display: inline-block;

            padding: 14px 25px;

            border-radius: 8px;

            margin-right: 10px;

            font-weight: bold;

            transition: 0.3s;
        }

        .btn-primary {

            background:
                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--red)
                );
        }

        .btn-secondary {

            border:
                1px solid rgba(255,255,255,0.1);

            background:
                rgba(255,255,255,0.03);
        }

        .btn:hover {

            transform:
                translateY(-5px);

            box-shadow:
                0 15px 35px
                rgba(255,23,68,0.25);
        }


        /* =====================================
           PROFILE
        ===================================== */

        .profile {

            width: 300px;

            height: 300px;

            margin: auto;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 35px;

            font-weight: bold;

            background: #050b18;

            border: 5px solid transparent;

            background:
                linear-gradient(#050b18,#050b18)
                padding-box,

                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--red)
                )
                border-box;

            box-shadow:
                0 0 60px
                rgba(22,119,255,0.3);

            animation:
                floating 4s ease-in-out infinite;
        }


        /* =====================================
           ABOUT
        ===================================== */

        #about {

            background: #0b1428;

            max-width: none;

            padding-left:
                max(30px, calc((100% - 1140px)/2));

            padding-right:
                max(30px, calc((100% - 1140px)/2));
        }

        .about-content {

            max-width: 800px;
        }

        .about-content p {

            color: var(--text);

            font-size: 18px;

            margin-bottom: 15px;
        }


        /* =====================================
           SKILLS
        ===================================== */

        .grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;
        }

        .card {

            background: var(--card);

            padding: 30px;

            border-radius: 15px;

            border:
                1px solid
                rgba(255,255,255,0.08);

            transition: 0.4s;
        }

        .card:hover {

            transform:
                translateY(-10px);

            border-color:
                var(--blue);

            box-shadow:
                0 20px 40px
                rgba(22,119,255,0.15);
        }

        .card h3 {

            font-size: 23px;

            margin-bottom: 10px;
        }

        .card p {

            color: var(--text);
        }


        /* =====================================
           PROJECTS
        ===================================== */

        #projects {

            max-width: none;

            background: #0b1428;

            padding-left:
                max(30px, calc((100% - 1140px)/2));

            padding-right:
                max(30px, calc((100% - 1140px)/2));
        }

        .project-card {

            min-height: 250px;

            position: relative;

            overflow: hidden;
        }

        .project-card::before {

            content: "";

            position: absolute;

            width: 100px;
            height: 100px;

            background: var(--red);

            filter: blur(70px);

            opacity: 0;

            right: -20px;
            top: -20px;

            transition: 0.4s;
        }

        .project-card:hover::before {

            opacity: 0.6;
        }


        /* =====================================
           CONTACT
        ===================================== */

        #contact {

            display: grid;

            grid-template-columns:
                0.8fr 1.2fr;

            gap: 60px;

            align-items: center;
        }

        .contact-text p {

            color: var(--text);

            font-size: 18px;
        }

        form {

            background: var(--card);

            padding: 35px;

            border-radius: 20px;

            border:
                1px solid
                rgba(255,255,255,0.08);
        }

        form div {

            margin-bottom: 20px;
        }

        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;
        }

        input,
        textarea {

            width: 100%;

            padding: 15px;

            border-radius: 8px;

            border:
                1px solid
                rgba(255,255,255,0.1);

            background: #071022;

            color: white;

            outline: none;

            font-size: 16px;
        }

        input:focus,
        textarea:focus {

            border-color: var(--blue);

            box-shadow:
                0 0 0 3px
                rgba(22,119,255,0.1);
        }

        textarea {

            resize: vertical;
        }

        form button {

            width: 100%;

            padding: 15px;

            border: none;

            border-radius: 8px;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            background:
                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--red)
                );

            transition: 0.3s;
        }

        form button:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 15px 35px
                rgba(255,23,68,0.25);
        }

        .success {

            padding: 15px;

            margin-bottom: 20px;

            border-radius: 8px;

            background:
                rgba(0,200,100,0.15);

            color: #55ff99;
        }


        /* =====================================
           FOOTER
        ===================================== */

        footer {

            padding: 30px;

            text-align: center;

            color: var(--text);

            background: #030711;

            border-top:
                1px solid
                rgba(255,255,255,0.08);
        }


        /* =====================================
           ANIMATIONS
        ===================================== */

        @keyframes slideLeft {

            from {
                opacity: 0;
                transform: translateX(-60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes floating {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes glow {

            from {
                transform: scale(0.9);
            }

            to {
                transform: scale(1.2);
            }
        }

        @keyframes textGlow {

            from {
                filter:
                    drop-shadow(
                        0 0 5px
                        rgba(22,119,255,0.2)
                    );
            }

            to {
                filter:
                    drop-shadow(
                        0 0 20px
                        rgba(255,23,68,0.4)
                    );
            }
        }


        /* =====================================
           MOBILE
        ===================================== */

        @media(max-width: 850px) {

            nav ul {
                display: none;
            }

            #home {

                grid-template-columns: 1fr;

                text-align: center;

                padding-top: 130px;
            }

            .description {
                margin-left: auto;
                margin-right: auto;
            }

            .profile {

                width: 220px;

                height: 220px;

                font-size: 25px;
            }

            .grid {

                grid-template-columns:
                    1fr 1fr;
            }

            #contact {

                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {

            section {
                padding: 80px 20px;
            }

            .grid {

                grid-template-columns: 1fr;
            }

            .hero-text h1 {
                font-size: 65px;
            }

            .hero-text h2 {
                font-size: 28px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================
     NAVIGATION
===================================== -->

<header>

    <nav>

        <div class="logo">

            <a href="#home">
                <?= $name ?><span>.</span>
            </a>

        </div>


        <ul>

            <li>
                <a href="#home">Home</a>
            </li>

            <li>
                <a href="#about">About</a>
            </li>

            <li>
                <a href="#skills">Skills</a>
            </li>

            <li>
                <a href="#projects">Projects</a>
            </li>

            <li>
                <a href="#services">Services</a>
            </li>

            <li>
                <a href="#contact">Contact</a>
            </li>

        </ul>

    </nav>

</header>


<!-- =====================================
     HOME
===================================== -->

<section id="home">

    <div class="hero-text">

        <p class="small-title">
            HELLO, I'M
        </p>

        <h1>
            <?= $name ?>
        </h1>

        <h2>
            <?= $role ?>
        </h2>

        <p class="description">
            <?= $description ?>
        </p>


        <a
            href="#projects"
            class="btn btn-primary">

            View Projects

        </a>


        <a
            href="#contact"
            class="btn btn-secondary">

            Contact Me

        </a>

    </div>


    <div>

        <div class="profile">

            Developer

        </div>

    </div>

</section>


<!-- =====================================
     ABOUT
===================================== -->

<section id="about">

    <div class="about-content">

        <p class="small-title">
            ABOUT ME
        </p>

        <h2>
            Building Ideas Into Software
        </h2>

        <p>
            I am a developer interested in
            Python, Flutter, Artificial Intelligence,
            Web Development and Backend Development.
        </p>

        <p>
            I enjoy learning technologies and
            building real-world software projects.
        </p>

    </div>

</section>


<!-- =====================================
     SKILLS
===================================== -->

<section id="skills">

    <p class="small-title">
        MY SKILLS
    </p>

    <h2>
        Technologies
    </h2>


    <div class="grid">

        <?php foreach ($skills as $skill): ?>

            <div class="card">

                <h3>
                    <?= htmlspecialchars($skill) ?>
                </h3>

                <p>
                    Development Technology
                </p>

            </div>

        <?php endforeach; ?>

    </div>

</section>


<!-- =====================================
     PROJECTS
===================================== -->

<section id="projects">

    <p class="small-title">
        MY WORK
    </p>

    <h2>
        Featured Projects
    </h2>


    <div class="grid">

        <?php foreach ($projects as $project): ?>

            <div class="card project-card">

                <h3>
                    <?= htmlspecialchars($project["title"]) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($project["description"]) ?>
                </p>

                <br>

                <a
                    href="#contact"
                    class="btn btn-secondary">

                    View Project

                </a>

            </div>

        <?php endforeach; ?>

    </div>

</section>


<!-- =====================================
     SERVICES
===================================== -->

<section id="services">

    <p class="small-title">
        SERVICES
    </p>

    <h2>
        What I Do
    </h2>


    <div class="grid">

        <?php foreach ($services as $service): ?>

            <div class="card">

                <h3>
                    <?= htmlspecialchars($service["title"]) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($service["description"]) ?>
                </p>

            </div>

        <?php endforeach; ?>

    </div>

</section>


<!-- =====================================
     CONTACT
===================================== -->

<section id="contact">

    <div class="contact-text">

        <p class="small-title">
            GET IN TOUCH
        </p>

        <h2>
            Let's Build Something Together
        </h2>

        <p>
            Have an idea or project?
            Send me a message.
        </p>

    </div>


    <div>

        <?php if ($message_sent): ?>

            <div class="success">

                Message received successfully!

            </div>

        <?php endif; ?>


        <form method="POST">

            <div>

                <label>
                    Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Your name"
                    required>

            </div>


            <div>

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Your email"
                    required>

            </div>


            <div>

                <label>
                    Message
                </label>

                <textarea
                    name="message"
                    rows="6"
                    placeholder="Your message..."
                    required></textarea>

            </div>


            <button type="submit">

                Send Message

            </button>

        </form>

    </div>

</section>


<!-- =====================================
     FOOTER
===================================== -->

<footer>

    <p>

        © <?= date("Y") ?>

        <?= $name ?>.

        All Rights Reserved.

    </p>

</footer>


</body>

</html>
```
