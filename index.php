<?php
/**
 * Signal — Telegram Mini App (single-file PHP build) with Adsgram Monetization
 */

declare(strict_types=1);

const BOT_TOKEN_FALLBACK = '8866862480:AAF2lxXYDJ6cbKXMI-6GRseng5hZ82ojfcE';
define('BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: BOT_TOKEN_FALLBACK);

/**
 * Validate a Telegram WebApp initData string against the bot token.
 */
function verify_telegram_init_data(string $initData, string $botToken): array
{
    parse_str($initData, $data);

    if (!isset($data['hash']) || $initData === '') {
        return ['ok' => false, 'reason' => 'missing_hash'];
    }

    $receivedHash = $data['hash'];
    unset($data['hash']);
    ksort($data);

    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[] = $key . '=' . $value;
    }
    $dataCheckString = implode("\n", $pairs);

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    $ok = hash_equals($computedHash, (string) $receivedHash);
    $authDate = isset($data['auth_date']) ? (int) $data['auth_date'] : 0;

    return [
        'ok'          => $ok,
        'auth_date'   => $authDate,
        'age_seconds' => $authDate > 0 ? (time() - $authDate) : null,
        'user'        => isset($data['user']) ? json_decode((string) $data['user'], true) : null,
    ];
}

/* -------------------------------------------------------------
   JSON API: POST /?action=verify
   ------------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_GET['action'] ?? '') === 'verify') {
    header('Content-Type: application/json');

    if (BOT_TOKEN === BOT_TOKEN_FALLBACK) {
        echo json_encode(['ok' => false, 'reason' => 'server_not_configured']);
        exit;
    }

    $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $initData = (string) ($body['initData'] ?? '');

    if ($initData === '') {
        echo json_encode(['ok' => false, 'reason' => 'empty_init_data']);
        exit;
    }

    echo json_encode(verify_telegram_init_data($initData, BOT_TOKEN));
    exit;
}

const PREMIUM_PRICE_STARS = 50;

function create_star_invoice_link(string $botToken, string $title, string $description, string $payload, int $amountStars): array
{
    $url = "https://api.telegram.org/bot{$botToken}/createInvoiceLink";
    $params = [
        'title'          => $title,
        'description'    => $description,
        'payload'        => $payload,
        'provider_token' => '',
        'currency'       => 'XTR',
        'prices'         => json_encode([['label' => $title, 'amount' => $amountStars]]),
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'reason' => 'curl_error: ' . $err];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params),
                'timeout' => 10,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return ['ok' => false, 'reason' => 'request_failed'];
        }
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        return ['ok' => false, 'reason' => $decoded['description'] ?? 'telegram_api_error'];
    }

    return ['ok' => true, 'link' => $decoded['result']];
}

/* -------------------------------------------------------------
   JSON API: POST /?action=create_invoice
   ------------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_GET['action'] ?? '') === 'create_invoice') {
    header('Content-Type: application/json');

    if (BOT_TOKEN === BOT_TOKEN_FALLBACK) {
        echo json_encode(['ok' => false, 'reason' => 'server_not_configured']);
        exit;
    }

    $result = create_star_invoice_link(
        BOT_TOKEN,
        'Premium Arcade Pass',
        'Unlocks Code Breaker and Word Signal permanently.',
        'premium_pack_' . bin2hex(random_bytes(6)),
        PREMIUM_PRICE_STARS
    );

    echo json_encode($result);
    exit;
}

/* -------------------------------------------------------------
   Telegram BOT WEBHOOK
   ------------------------------------------------------------- */
$rawBody = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? file_get_contents('php://input') : '';
$update = $rawBody ? json_decode($rawBody, true) : null;

if (is_array($update) && isset($update['update_id']) && !isset($_GET['action'])) {
    header('Content-Type: application/json');

    if (isset($update['pre_checkout_query'])) {
        $queryId = $update['pre_checkout_query']['id'];
        @file_get_contents(
            "https://api.telegram.org/bot" . BOT_TOKEN . "/answerPreCheckoutQuery"
            . "?pre_checkout_query_id=" . urlencode($queryId) . "&ok=true"
        );
        echo json_encode(['ok' => true]);
        exit;
    }

    if (isset($update['message']['successful_payment'])) {
        $payment = $update['message']['successful_payment'];
        $userId  = $update['message']['from']['id'] ?? 'unknown';
        write_payment_log($userId, $payment['telegram_payment_charge_id'] ?? '', (int) ($payment['total_amount'] ?? 0));
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

function write_payment_log(string $userId, string $chargeId, int $amountStars): void
{
    $line = sprintf("%s\tuser=%s\tcharge=%s\tstars=%d\n", date('c'), $userId, $chargeId, $amountStars);
    @file_put_contents(__DIR__ . '/payments.log', $line, FILE_APPEND | LOCK_EX);
}

$serverTime   = date('Y-m-d H:i:s \U\T\C');
$phpVersion   = phpversion();
$isConfigured = BOT_TOKEN !== BOT_TOKEN_FALLBACK;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no" />
<title>Signal — Mini App</title>

<!-- Telegram WebApp SDK -->
<script src="https://telegram.org/js/telegram-web-app.js"></script>

<!-- Adsgram SDK Integration -->
<script src="https://sad.adsgram.ai/js/sad.min.js"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
:root {
  --bg-deep: #05070f;
  --bg-panel: #0d1220;
  --bg-panel-raised: #121a2e;
  --line: rgba(140, 160, 220, 0.12);
  --line-strong: rgba(140, 160, 220, 0.22);
  --blue: #3b5bfe;
  --blue-soft: #6f86ff;
  --blue-dim: #16204a;
  --red: #ff3b4e;
  --red-soft: #ff6b78;
  --red-dim: #3a1420;
  --green: #3ee08f;
  --text-primary: #eef1fb;
  --text-muted: #8790b3;
  --text-faint: #4d5578;
  --font-display: "Oxanium", sans-serif;
  --font-body: "Inter", sans-serif;
  --font-mono: "JetBrains Mono", monospace;
  --radius-lg: 20px;
  --radius-md: 14px;
  --radius-sm: 9px;
  color-scheme: dark;
}

* { box-sizing: border-box; }
html, body {
  margin: 0; padding: 0;
  background: var(--bg-deep); color: var(--text-primary);
  font-family: var(--font-body);
  -webkit-tap-highlight-color: transparent;
  overscroll-behavior-y: none;
}
button, input { font-family: inherit; color: inherit; }
button { border: none; background: none; cursor: pointer; }

#app {
  position: relative; z-index: 1;
  max-width: 480px; margin: 0 auto;
  min-height: 100vh; padding-bottom: 88px;
  display: flex; flex-direction: column;
}

.app-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 18px 14px; border-bottom: 1px solid var(--line);
  background: linear-gradient(180deg, rgba(59, 91, 254, 0.08), transparent);
}
.header-left { display: flex; align-items: center; gap: 12px; }
.avatar-ring {
  width: 44px; height: 44px; border-radius: 50%;
  background: linear-gradient(140deg, var(--blue), var(--red)); padding: 2px;
}
.avatar-fallback {
  width: 100%; height: 100%; border-radius: 50%;
  background: var(--bg-panel-raised); display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-weight: 700; color: var(--blue-soft);
}
.header-name { margin: 0; font-family: var(--font-display); font-size: 16px; font-weight: 700; }

.view { flex: 1; padding: 18px 16px 8px; display: flex; flex-direction: column; gap: 14px; }
.panel { background: var(--bg-panel); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 16px; }
.panel-eyebrow { font-family: var(--font-mono); font-size: 10.5px; text-transform: uppercase; color: var(--text-faint); }

.btn {
  width: 100%; padding: 13px 16px; border-radius: var(--radius-md);
  font-family: var(--font-display); font-weight: 700; font-size: 14px;
}
.btn-primary { background: linear-gradient(120deg, var(--blue), #5f3bfe 130%); color: #fff; }
.btn-ad { background: linear-gradient(120deg, #3ee08f, #229954); color: #000; margin-top: 10px; }

.bottom-nav {
  position: fixed; left: 50%; bottom: 0; transform: translateX(-50%);
  width: 100%; max-width: 480px; display: flex;
  background: rgba(13, 18, 32, 0.9); border-top: 1px solid var(--line);
  padding: 8px 10px; z-index: 5;
}
.nav-btn { flex: 1; display: flex; flex-direction: column; align-items: center; color: var(--text-faint); }
.nav-btn.is-active { color: var(--red-soft); }
</style>
</head>
<body>

<div id="app">
  <header class="app-header">
    <div class="header-left">
      <div class="avatar-ring"><span id="userInitial" class="avatar-fallback">U</span></div>
      <div>
        <h1 id="userName" class="header-name">Operator</h1>
      </div>
    </div>
    <div>
      <span style="font-family: var(--font-mono); font-size: 12px; color: var(--green);">Points: <span id="userPoints">0</span></span>
    </div>
  </header>

  <main class="view" id="view-home">
    <section class="panel">
      <span class="panel-eyebrow">01 — Adsgram Monetization</span>
      <p style="font-size: 13px; color: var(--text-muted); margin: 8px 0;">Watch a video ad to earn reward points for your account.</p>
      <button class="btn btn-ad" onclick="showRewardAd()">📺 Watch Ad (+50 Points)</button>
      <p id="adStatus" style="font-size: 11px; color: var(--text-faint); margin-top: 6px;"></p>
    </section>

    <section class="panel">
      <span class="panel-eyebrow">02 — Identity</span>
      <p style="font-size: 13px;">User ID: <span id="stat-userid" style="font-family: var(--font-mono);">—</span></p>
    </section>
  </main>

  <nav class="bottom-nav">
    <button class="nav-btn is-active"><span>HOME</span></button>
  </nav>
</div>

<script>
  const tg = window.Telegram?.WebApp;
  let points = 0;

  if (tg) {
    tg.ready();
    tg.expand();
    if (tg.initDataUnsafe?.user) {
      document.getElementById('userName').innerText = tg.initDataUnsafe.user.first_name || 'Operator';
      document.getElementById('userInitial').innerText = (tg.initDataUnsafe.user.first_name || 'U')[0];
      document.getElementById('stat-userid').innerText = tg.initDataUnsafe.user.id || '—';
    }
  }

  // Adsgram Integration Setup
  // 👉 <u>ADSGRAM BLOCK ID</u> ያስገቡ (ከ Adsgram Dashboard ያገኙትን ID ይተኩ)
  const ADSGRAM_BLOCK_ID = '47164'; 

  function showRewardAd() {
    const statusEl = document.getElementById('adStatus');
    statusEl.innerText = "Loading ad...";

    if (typeof Adsgram === 'undefined') {
      statusEl.innerText = "Adsgram SDK not loaded properly.";
      return;
    }

    const AdController = Adsgram.init({ blockId: ADSGRAM_BLOCK_ID });

    AdController.show().then((result) => {
      // ተጫዋቹ ማስታወቂያውን ጨርሶ ሲያይ
      points += 50;
      document.getElementById('userPoints').innerText = points;
      statusEl.innerText = "Success! You earned +50 points.";
    }).catch((result) => {
      // ማስታወቂያው ሳይጨረስ ሲዘጋ ወይም ስህተት ሲፈጠር
      statusEl.innerText = "Ad skipped or failed to load.";
    });
  }
</script>
</body>
</html>
