<!DOCTYPE html>
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

    <?php
    if (isset($_POST['submit_score'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $score = $_POST['score'];
        $data = "User ID: $user_id | Username: $username | Score: $score\n";
        file_put_contents('scores.txt', $data, FILE_APPEND);
        exit;
    }
    ?>
</body>
</html>
