<?php
/**
 * Signal — Telegram Mini App (single-file PHP build)
 * -----------------------------------------------------------
 * One file serves the whole app AND handles a small JSON API
 * endpoint that verifies Telegram's `initData` server-side
 * (HMAC-SHA256 check against your bot token), which is the one
 * piece of this app that genuinely needs a server.
 *
 * Setup:
 *   1. Set your bot token as an environment variable:
 *        export TELEGRAM_BOT_TOKEN="123456:ABC-your-real-token"
 *      (or edit the fallback string below — fine for local
 *      testing, but use the env var in production).
 *   2. Serve this file with PHP's built-in server:
 *        php -S localhost:8000 index.php
 *   3. Point your Mini App's Web App URL (set via @BotFather)
 *      at wherever you deploy this file over HTTPS.
 */

declare(strict_types=1);

const BOT_TOKEN_FALLBACK = 'YOUR_BOT_TOKEN_HERE';
define('BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: BOT_TOKEN_FALLBACK);

/**
 * Validate a Telegram WebApp initData string against the bot token.
 * See: https://core.telegram.org/bots/webapps#validating-data-received-via-the-web-app
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
   JSON API: POST /?action=verify   { "initData": "..." }
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

/**
 * Ask Telegram to mint a payable invoice link for a Telegram Stars purchase.
 * Stars payments need no payment-provider token — currency is literally "XTR"
 * and amounts are whole Stars (no minor-unit conversion).
 * See: https://core.telegram.org/bots/api#createinvoicelink
 */
function create_star_invoice_link(string $botToken, string $title, string $description, string $payload, int $amountStars): array
{
    $url = "https://api.telegram.org/bot{$botToken}/createInvoiceLink";
    $params = [
        'title'          => $title,
        'description'    => $description,
        'payload'        => $payload,
        'provider_token' => '', // empty for Telegram Stars
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
   Body: { "item": "premium_pack" }
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
   Telegram BOT WEBHOOK (optional but recommended for production)
   Register this file's URL as your bot's webhook
   (https://api.telegram.org/bot<token>/setWebhook?url=...) and
   Telegram will POST raw Update objects here with no `action`
   query param. Two things matter for Stars payments:
     1. pre_checkout_query MUST be answered within 10s or the
        payment is rejected client-side.
     2. successful_payment is the only trustworthy confirmation
        that Stars actually changed hands — the client-side
        openInvoice() callback can be spoofed, so real apps
        should persist unlocks keyed off this event (and the
        user id in it) rather than trusting localStorage alone.
   This demo just appends a log line since there's no database;
   swap writeToPaymentsLog() for a real persistence layer.
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

/* -------------------------------------------------------------
   Otherwise, render the page. A few small server-side facts are
   exposed to the client so it's obvious PHP is actually involved.
   ------------------------------------------------------------- */
$serverTime  = date('Y-m-d H:i:s \U\T\C');
$phpVersion  = phpversion();
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* =========================================================
   TOKENS
   ========================================================= */
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
  margin: 0;
  padding: 0;
  background: var(--bg-deep);
  color: var(--text-primary);
  font-family: var(--font-body);
  -webkit-tap-highlight-color: transparent;
  overscroll-behavior-y: none;
}

button, input { font-family: inherit; color: inherit; }
button { border: none; background: none; cursor: pointer; }

svg {
  width: 22px;
  height: 22px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.6;
  stroke-linecap: round;
  stroke-linejoin: round;
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.001ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.001ms !important;
  }
}

.grid-backdrop {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background-image:
    linear-gradient(var(--line) 1px, transparent 1px),
    linear-gradient(90deg, var(--line) 1px, transparent 1px);
  background-size: 34px 34px;
  -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 100%);
  mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 100%);
  opacity: 0.5;
}

body::after {
  content: "";
  position: fixed;
  left: 50%;
  bottom: -20%;
  width: 140%;
  height: 40%;
  transform: translateX(-50%);
  background: radial-gradient(ellipse at center, rgba(255, 59, 78, 0.10), transparent 70%);
  pointer-events: none;
  z-index: 0;
}

#app {
  position: relative;
  z-index: 1;
  max-width: 480px;
  margin: 0 auto;
  min-height: 100vh;
  padding-bottom: 88px;
  display: flex;
  flex-direction: column;
}

/* HEADER */
.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 18px 14px;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(180deg, rgba(59, 91, 254, 0.08), transparent);
}

.header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }

.avatar-ring {
  position: relative;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  flex-shrink: 0;
  background: linear-gradient(140deg, var(--blue), var(--red));
  padding: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-img {
  width: 100%; height: 100%;
  border-radius: 50%;
  object-fit: cover;
  display: none;
  background: var(--bg-panel-raised);
}
.avatar-img[src] { display: block; }

.avatar-fallback {
  width: 100%; height: 100%;
  border-radius: 50%;
  background: var(--bg-panel-raised);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display);
  font-weight: 700; font-size: 15px;
  color: var(--blue-soft);
}

.header-text { min-width: 0; }

.header-eyebrow {
  margin: 0;
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--text-faint);
}

.header-name {
  margin: 2px 0 0;
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

.conn-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--red);
  box-shadow: 0 0 8px 1px rgba(255, 59, 78, 0.7);
  transition: background 0.3s, box-shadow 0.3s;
}
.conn-dot.is-live { background: var(--green); box-shadow: 0 0 8px 1px rgba(62, 224, 143, 0.7); }

.conn-label {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.1em;
  color: var(--text-faint);
}

/* VIEWS */
.view {
  flex: 1;
  padding: 18px 16px 8px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  animation: view-in 0.25s ease both;
}
.is-hidden { display: none !important; }

@keyframes view-in {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

.panel {
  background: var(--bg-panel);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  padding: 16px;
}

.panel-head { display: flex; align-items: center; margin-bottom: 10px; }

.panel-eyebrow {
  font-family: var(--font-mono);
  font-size: 10.5px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--text-faint);
}

.hint-text { margin: 0 0 10px; font-size: 12.5px; color: var(--text-muted); }

/* HOME */
.id-grid { margin: 0; display: grid; gap: 10px; }

.id-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--line);
}
.id-row:last-child { border-bottom: none; padding-bottom: 0; }
.id-row dt { font-size: 13px; color: var(--text-muted); }
.id-row dd {
  margin: 0;
  font-family: var(--font-mono);
  font-size: 13px;
  color: var(--text-primary);
  text-align: right;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 60%;
}

.badge {
  font-family: var(--font-mono);
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid var(--line-strong);
}
.badge--ok { color: var(--green); border-color: rgba(62,224,143,0.4); }
.badge--fail { color: var(--red-soft); border-color: rgba(255,59,78,0.4); }
.badge--pending { color: var(--text-faint); }

.best-score-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 14px; }

.best-score-num {
  font-family: var(--font-display);
  font-size: 40px;
  font-weight: 800;
  background: linear-gradient(120deg, var(--blue-soft), var(--red-soft));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.best-score-label { font-size: 12.5px; color: var(--text-muted); }

.code-block {
  margin: 0;
  padding: 12px;
  background: var(--bg-deep);
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  font-family: var(--font-mono);
  font-size: 11px;
  line-height: 1.6;
  color: var(--blue-soft);
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 120px;
  overflow-y: auto;
}

/* BUTTONS */
.btn {
  width: 100%;
  padding: 13px 16px;
  border-radius: var(--radius-md);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.02em;
  transition: transform 0.12s ease, opacity 0.12s ease;
}
.btn:active { transform: scale(0.98); }
.btn-primary {
  background: linear-gradient(120deg, var(--blue), #5f3bfe 130%);
  color: #fff;
  box-shadow: 0 8px 24px -8px rgba(59, 91, 254, 0.6);
}
.btn-outline {
  background: transparent;
  border: 1px solid var(--line-strong);
  color: var(--blue-soft);
}

/* GAME */
.game-scoreboard { display: flex; justify-content: space-between; padding: 14px 18px; }
.score-cell { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.score-cell-label {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-faint);
}
.score-cell-value {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  color: var(--red-soft);
  font-variant-numeric: tabular-nums;
}
.score-cell-value--muted { color: var(--text-muted); }

.tap-arena {
  position: relative;
  flex: 1;
  min-height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.tap-rings {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  pointer-events: none;
}
.ring { position: absolute; border-radius: 50%; border: 1px solid var(--line-strong); }
.ring-1 { width: 150px; height: 150px; }
.ring-2 { width: 210px; height: 210px; border-color: var(--line); }
.ring-3 { width: 270px; height: 270px; border-color: rgba(140,160,220,0.06); }

.tap-core {
  position: relative;
  width: 168px; height: 168px;
  border-radius: 50%;
  background: radial-gradient(circle at 35% 30%, var(--blue-soft), var(--blue) 55%, #10193a 100%);
  box-shadow:
    0 0 0 1px var(--line-strong),
    0 20px 50px -15px rgba(59, 91, 254, 0.55),
    inset 0 -10px 30px rgba(0,0,0,0.35);
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 20px;
  letter-spacing: 0.08em;
  color: #fff;
  transition: transform 0.08s ease, box-shadow 0.15s ease;
}
.tap-core:active {
  transform: scale(0.94);
  box-shadow:
    0 0 0 1px var(--line-strong),
    0 8px 24px -10px rgba(255, 59, 78, 0.6),
    inset 0 -6px 20px rgba(0,0,0,0.4);
}
.tap-core.is-pulsing { animation: core-pulse 0.18s ease; }
@keyframes core-pulse {
  0% { box-shadow: 0 0 0 0 rgba(255, 59, 78, 0.55), inset 0 -10px 30px rgba(0,0,0,0.35); }
  100% { box-shadow: 0 0 0 22px rgba(255, 59, 78, 0), inset 0 -10px 30px rgba(0,0,0,0.35); }
}
.tap-core.is-done {
  background: radial-gradient(circle at 35% 30%, #4d5578, #1a2036 60%, #0a0e1a 100%);
}

.game-status { text-align: center; font-size: 13px; color: var(--text-muted); min-height: 18px; }

/* GAME HUB */
.game-screen {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  animation: view-in 0.2s ease both;
}

.back-btn {
  align-self: flex-start;
  font-family: var(--font-mono);
  font-size: 11.5px;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  padding: 6px 12px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--bg-panel);
}

.game-menu-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.game-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 16px 14px;
  background: var(--bg-panel);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  text-align: left;
  transition: transform 0.12s ease, border-color 0.12s ease;
}
.game-card:active { transform: scale(0.97); border-color: var(--line-strong); }

.game-card-icon {
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px;
  font-size: 16px;
  margin-bottom: 2px;
}
.game-card-icon--blue { background: var(--blue-dim); color: var(--blue-soft); }
.game-card-icon--red { background: var(--red-dim); color: var(--red-soft); }

.game-card-name {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 700;
}

.game-card-desc {
  font-size: 11.5px;
  color: var(--text-muted);
  line-height: 1.35;
}

.game-card-best {
  margin-top: 4px;
  font-family: var(--font-mono);
  font-size: 10.5px;
  color: var(--text-faint);
}

/* REFLEX TEST */
.reflex-box {
  width: 100%;
  height: 220px;
  border-radius: var(--radius-lg);
  background: var(--bg-panel-raised);
  border: 1px solid var(--line-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 15px;
  color: var(--text-muted);
  transition: background 0.15s ease, border-color 0.15s ease;
}
.reflex-box.is-waiting {
  background: linear-gradient(140deg, var(--red-dim), var(--bg-panel-raised));
  color: var(--red-soft);
}
.reflex-box.is-armed {
  background: linear-gradient(140deg, #124a33, #0e2f22);
  border-color: rgba(62,224,143,0.5);
  color: var(--green);
}
.reflex-box.is-toosoon {
  background: linear-gradient(140deg, var(--red), #7a1522);
  color: #fff;
}

/* MEMORY GRID */
.memory-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.memory-card {
  aspect-ratio: 1;
  border-radius: var(--radius-sm);
  background: var(--bg-panel-raised);
  border: 1px solid var(--line);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  perspective: 400px;
}

.memory-card-face {
  color: var(--text-faint);
  font-family: var(--font-mono);
  font-size: 14px;
}

.memory-card.is-flipped,
.memory-card.is-matched {
  background: var(--bg-panel);
  border-color: var(--line-strong);
}

.memory-card.is-matched { opacity: 0.45; }

/* QUIZ */
.quiz-panel { display: flex; flex-direction: column; gap: 12px; }

.quiz-question {
  margin: 0;
  font-family: var(--font-display);
  font-size: 15.5px;
  font-weight: 700;
  line-height: 1.4;
}

.quiz-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.quiz-option {
  text-align: left;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: var(--bg-panel-raised);
  border: 1px solid var(--line);
  font-size: 13.5px;
  transition: border-color 0.12s ease, background 0.12s ease;
}
.quiz-option:active { transform: scale(0.98); }
.quiz-option.is-correct {
  background: rgba(62, 224, 143, 0.12);
  border-color: rgba(62, 224, 143, 0.5);
  color: var(--green);
}
.quiz-option.is-wrong {
  background: var(--red-dim);
  border-color: rgba(255, 59, 78, 0.5);
  color: var(--red-soft);
}
.quiz-option.is-disabled { pointer-events: none; opacity: 0.7; }

/* PREMIUM LOCK BADGE */
.game-card.is-premium { position: relative; }
.premium-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  font-family: var(--font-mono);
  font-size: 9.5px;
  padding: 2px 7px;
  border-radius: 999px;
  background: rgba(255, 59, 78, 0.12);
  border: 1px solid rgba(255, 59, 78, 0.4);
  color: var(--red-soft);
}
.premium-badge.is-unlocked {
  background: rgba(62, 224, 143, 0.12);
  border-color: rgba(62, 224, 143, 0.4);
  color: var(--green);
}

/* STORE */
.store-hero { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; }
.store-title {
  margin: 2px 0 0;
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 800;
}
.store-price-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin: 10px 0 14px;
}
.store-price {
  font-family: var(--font-display);
  font-size: 28px;
  font-weight: 800;
  color: var(--red-soft);
}
.store-price-label {
  font-size: 12px;
  color: var(--text-muted);
}
.store-hero .btn { margin-top: 6px; }
.store-status {
  margin: 10px 0 0;
  font-size: 12.5px;
  color: var(--text-muted);
  min-height: 16px;
}

/* CODE BREAKER */
.cb-history {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 160px;
  overflow-y: auto;
}
.cb-history-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-family: var(--font-mono);
  font-size: 13px;
  padding: 6px 4px;
  border-bottom: 1px solid var(--line);
}
.cb-history-row:last-child { border-bottom: none; }
.cb-history-pegs { letter-spacing: 2px; color: var(--text-muted); }

.cb-input-row {
  display: flex;
  justify-content: center;
  gap: 10px;
}
.cb-digit-slot {
  width: 44px;
  height: 52px;
  border-radius: var(--radius-sm);
  background: var(--bg-panel-raised);
  border: 1px solid var(--line-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  color: var(--blue-soft);
}
.cb-keypad {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}
.cb-key {
  padding: 12px 0;
  border-radius: var(--radius-sm);
  background: var(--bg-panel);
  border: 1px solid var(--line);
  font-family: var(--font-mono);
  font-size: 15px;
  text-align: center;
}
.cb-key:active { transform: scale(0.95); }

/* WORD SIGNAL */
.ws-answer-slots {
  display: flex;
  justify-content: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.ws-slot {
  width: 34px;
  height: 40px;
  border-radius: var(--radius-sm);
  background: var(--bg-panel-raised);
  border: 1px solid var(--line-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 17px;
  font-weight: 700;
  color: var(--red-soft);
}
.ws-letter-tiles {
  display: flex;
  justify-content: center;
  gap: 6px;
  flex-wrap: wrap;
}
.ws-tile {
  width: 38px;
  height: 44px;
  border-radius: var(--radius-sm);
  background: var(--bg-panel);
  border: 1px solid var(--line);
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 700;
}
.ws-tile:active { transform: scale(0.95); }
.ws-tile.is-used { opacity: 0.25; pointer-events: none; }
.ws-actions { display: flex; gap: 10px; }
.ws-actions .btn { flex: 1; }

/* MEDIA */
.player-panel { display: flex; flex-direction: column; align-items: center; text-align: center; }

.cover-frame {
  position: relative;
  width: 130px; height: 130px;
  margin-bottom: 14px;
  display: flex; align-items: center; justify-content: center;
}
.cover-glow {
  position: absolute; inset: -20px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 59, 78, 0.25), transparent 70%);
  filter: blur(2px);
}
.cover-disc {
  position: relative;
  width: 118px; height: 118px;
  border-radius: 50%;
  background: repeating-radial-gradient(circle, var(--bg-panel-raised) 0 2px, #0a1020 2px 4px);
  border: 1px solid var(--line-strong);
  display: flex; align-items: center; justify-content: center;
  transition: transform 6s linear;
}
.cover-disc.is-spinning { animation: disc-spin 6s linear infinite; }
@keyframes disc-spin { to { transform: rotate(360deg); } }
.cover-disc-hole {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(140deg, var(--blue), var(--red));
}

.track-title { margin: 0; font-family: var(--font-display); font-size: 17px; font-weight: 700; }
.track-sub { margin: 3px 0 14px; font-size: 12.5px; color: var(--text-muted); }

.wave-canvas { width: 100%; height: 56px; margin-bottom: 6px; }

.scrub-row { width: 100%; display: flex; align-items: center; gap: 8px; }
.time-label { font-family: var(--font-mono); font-size: 10.5px; color: var(--text-faint); min-width: 30px; }

.scrub-bar {
  flex: 1;
  -webkit-appearance: none;
  appearance: none;
  height: 4px;
  border-radius: 2px;
  background: linear-gradient(90deg, var(--red) 0%, var(--red) var(--fill, 0%), var(--line-strong) var(--fill, 0%), var(--line-strong) 100%);
  outline: none;
}
.scrub-bar::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 13px; height: 13px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(255, 59, 78, 0.35);
  cursor: pointer;
}
.scrub-bar::-moz-range-thumb {
  width: 13px; height: 13px;
  border: none;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(255, 59, 78, 0.35);
  cursor: pointer;
}
.scrub-bar--thin { height: 3px; }

.transport-row { display: flex; align-items: center; justify-content: center; gap: 22px; margin: 18px 0 14px; }
.transport-btn {
  width: 42px; height: 42px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-primary);
  background: var(--bg-panel-raised);
  border: 1px solid var(--line);
}
.transport-btn--main {
  width: 60px; height: 60px;
  background: linear-gradient(120deg, var(--blue), var(--red));
  box-shadow: 0 10px 26px -10px rgba(255, 59, 78, 0.6);
}
.transport-btn--main svg { width: 26px; height: 26px; }
.transport-btn svg { fill: currentColor; }

.volume-row { width: 100%; display: flex; align-items: center; gap: 10px; }
.vol-icon { width: 16px; height: 16px; color: var(--text-faint); fill: currentColor; flex-shrink: 0; }

.track-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
.track-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 6px;
  border-radius: var(--radius-sm);
  border-bottom: 1px solid var(--line);
}
.track-item:last-child { border-bottom: none; }
.track-item.is-active { background: linear-gradient(90deg, rgba(59,91,254,0.12), rgba(255,59,78,0.08)); }
.track-item-idx { font-family: var(--font-mono); font-size: 11px; color: var(--text-faint); width: 16px; flex-shrink: 0; }
.track-item-body { flex: 1; min-width: 0; }
.track-item-name { font-size: 13.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.track-item-artist { font-size: 11.5px; color: var(--text-muted); }
.track-item-dur { font-family: var(--font-mono); font-size: 11px; color: var(--text-faint); flex-shrink: 0; }

/* BOTTOM NAV */
.bottom-nav {
  position: fixed;
  left: 50%; bottom: 0;
  transform: translateX(-50%);
  width: 100%; max-width: 480px;
  display: flex;
  background: rgba(13, 18, 32, 0.9);
  backdrop-filter: blur(14px);
  border-top: 1px solid var(--line);
  padding: 8px 10px calc(8px + env(safe-area-inset-bottom));
  z-index: 5;
}
.nav-btn {
  flex: 1;
  display: flex; flex-direction: column; align-items: center; gap: 3px;
  padding: 6px 0;
  color: var(--text-faint);
  border-radius: var(--radius-sm);
}
.nav-btn span { font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.04em; }
.nav-btn.is-active { color: var(--red-soft); }
.nav-btn.is-active svg { stroke: var(--red-soft); }
</style>
</head>
<body>

<div class="grid-backdrop" aria-hidden="true"></div>

<div id="app">

  <!-- ===================== HEADER ===================== -->
  <header class="app-header">
    <div class="header-left">
      <div class="avatar-ring">
        <img id="userAvatar" class="avatar-img" alt="" />
        <span id="userInitial" class="avatar-fallback"></span>
      </div>
      <div class="header-text">
        <p class="header-eyebrow">Signal · Session</p>
        <h1 id="userName" class="header-name">Guest Operator</h1>
      </div>
    </div>
    <div class="header-right">
      <span id="connDot" class="conn-dot"></span>
      <span id="connLabel" class="conn-label">SYNCING</span>
    </div>
  </header>

  <!-- ===================== VIEW: HOME ===================== -->
  <main class="view" id="view-home" data-view="home">

    <section class="panel id-panel">
      <div class="panel-head"><span class="panel-eyebrow">01 — Identity</span></div>
      <dl class="id-grid">
        <div class="id-row"><dt>User ID</dt><dd id="stat-userid">—</dd></div>
        <div class="id-row"><dt>Username</dt><dd id="stat-username">—</dd></div>
        <div class="id-row"><dt>Language</dt><dd id="stat-lang">—</dd></div>
        <div class="id-row"><dt>Premium</dt><dd id="stat-premium">—</dd></div>
        <div class="id-row"><dt>Platform</dt><dd id="stat-platform">—</dd></div>
        <div class="id-row"><dt>Color Scheme</dt><dd id="stat-colorscheme">—</dd></div>
        <div class="id-row"><dt>Server Check</dt><dd id="stat-serververify"><span class="badge badge--pending">PENDING</span></dd></div>
      </dl>
    </section>

    <section class="panel">
      <div class="panel-head"><span class="panel-eyebrow">02 — Best Score</span></div>
      <div class="best-score-row">
        <span class="best-score-num" id="home-best-score">0</span>
        <span class="best-score-label">best tap sprint</span>
      </div>
      <button class="btn btn-outline" id="goToGameBtn">Open the games arcade →</button>
    </section>

    <section class="panel">
      <div class="panel-head"><span class="panel-eyebrow">03 — Server</span></div>
      <dl class="id-grid">
        <div class="id-row"><dt>Rendered at</dt><dd><?= htmlspecialchars($serverTime, ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div class="id-row"><dt>PHP version</dt><dd><?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div class="id-row"><dt>Bot token set</dt><dd><?= $isConfigured ? 'Yes' : 'No — edit BOT_TOKEN_FALLBACK or set TELEGRAM_BOT_TOKEN' ?></dd></div>
      </dl>
    </section>

    <section class="panel">
      <div class="panel-head"><span class="panel-eyebrow">04 — Raw initData</span></div>
      <p class="hint-text">Diagnostic only — this is what Telegram actually handed the app.</p>
      <pre class="code-block" id="raw-initdata">unavailable</pre>
    </section>

  </main>

  <!-- ===================== VIEW: GAME ===================== -->
  <main class="view is-hidden" id="view-game" data-view="game">

    <!-- ---------- GAME MENU ---------- -->
    <section class="game-screen" id="gameScreen-menu" data-game-screen="menu">
      <div class="game-menu-grid">
        <button class="game-card" data-open-game="tap">
          <span class="game-card-icon game-card-icon--blue">◎</span>
          <span class="game-card-name">Tap Sprint</span>
          <span class="game-card-desc">Tap as fast as you can in 10s</span>
          <span class="game-card-best" id="menu-best-tap">Best: 0</span>
        </button>
        <button class="game-card" data-open-game="reaction">
          <span class="game-card-icon game-card-icon--red">⚡</span>
          <span class="game-card-name">Reflex Test</span>
          <span class="game-card-desc">React the instant it turns green</span>
          <span class="game-card-best" id="menu-best-reaction">Best: —</span>
        </button>
        <button class="game-card" data-open-game="memory">
          <span class="game-card-icon game-card-icon--blue">▦</span>
          <span class="game-card-name">Memory Grid</span>
          <span class="game-card-desc">Clear all pairs in the fewest moves</span>
          <span class="game-card-best" id="menu-best-memory">Best: —</span>
        </button>
        <button class="game-card" data-open-game="quiz">
          <span class="game-card-icon game-card-icon--red">?</span>
          <span class="game-card-name">Quick Quiz</span>
          <span class="game-card-desc">Six rapid-fire signal trivia questions</span>
          <span class="game-card-best" id="menu-best-quiz">Best: —</span>
        </button>
        <button class="game-card is-premium" data-open-game="codebreaker" data-premium="true">
          <span class="game-card-icon game-card-icon--blue">✱</span>
          <span class="game-card-name">Code Breaker</span>
          <span class="game-card-desc">Crack the 4-digit signal code</span>
          <span class="game-card-best" id="menu-best-codebreaker">Best: —</span>
          <span class="premium-badge" id="lockbadge-codebreaker">🔒 Premium</span>
        </button>
        <button class="game-card is-premium" data-open-game="wordsignal" data-premium="true">
          <span class="game-card-icon game-card-icon--red">▤</span>
          <span class="game-card-name">Word Signal</span>
          <span class="game-card-desc">Unscramble signal &amp; tech terms</span>
          <span class="game-card-best" id="menu-best-wordsignal">Best: —</span>
          <span class="premium-badge" id="lockbadge-wordsignal">🔒 Premium</span>
        </button>
      </div>
    </section>

    <!-- ---------- CODE BREAKER ---------- -->
    <section class="game-screen is-hidden" id="gameScreen-codebreaker" data-game-screen="codebreaker">
      <button class="back-btn" data-back-to-menu>← Games</button>

      <section class="panel game-scoreboard">
        <div class="score-cell"><span class="score-cell-label">Guesses</span><span class="score-cell-value" id="cb-guesses">0/8</span></div>
        <div class="score-cell"><span class="score-cell-label">Best</span><span class="score-cell-value score-cell-value--muted" id="cb-best">—</span></div>
      </section>

      <section class="panel">
        <p class="hint-text">Crack the 4-digit code (each digit 0–7). ● = right digit, right spot. ○ = right digit, wrong spot.</p>
        <div class="cb-history" id="cbHistory"></div>
      </section>

      <div class="cb-input-row" id="cbInputRow"></div>
      <div class="cb-keypad" id="cbKeypad"></div>

      <div class="game-status" id="cbStatus">Build a 4-digit guess, then submit.</div>
      <button class="btn btn-primary" id="cbSubmitBtn">Submit guess</button>
      <button class="btn btn-outline is-hidden" id="cbRestartBtn">Try a new code</button>
    </section>

    <!-- ---------- WORD SIGNAL ---------- -->
    <section class="game-screen is-hidden" id="gameScreen-wordsignal" data-game-screen="wordsignal">
      <button class="back-btn" data-back-to-menu>← Games</button>

      <section class="panel game-scoreboard">
        <div class="score-cell"><span class="score-cell-label">Score</span><span class="score-cell-value" id="ws-score">0</span></div>
        <div class="score-cell"><span class="score-cell-label">Best</span><span class="score-cell-value score-cell-value--muted" id="ws-best">—</span></div>
        <div class="score-cell"><span class="score-cell-label">Word</span><span class="score-cell-value" id="ws-progress">1/8</span></div>
      </section>

      <section class="panel">
        <p class="hint-text" id="wsHint">Hint appears here.</p>
        <div class="ws-answer-slots" id="wsAnswerSlots"></div>
        <div class="ws-letter-tiles" id="wsLetterTiles"></div>
      </section>

      <div class="game-status" id="wsStatus">Tap letters to spell the word.</div>
      <div class="ws-actions">
        <button class="btn btn-outline" id="wsClearBtn">Clear</button>
        <button class="btn btn-primary" id="wsSubmitBtn">Submit</button>
      </div>
      <button class="btn btn-primary is-hidden" id="wsRestartBtn">Play again</button>
    </section>

    <!-- ---------- TAP SPRINT ---------- -->
    <section class="game-screen is-hidden" id="gameScreen-tap" data-game-screen="tap">
      <button class="back-btn" data-back-to-menu>← Games</button>

      <section class="panel game-scoreboard">
        <div class="score-cell"><span class="score-cell-label">Score</span><span class="score-cell-value" id="game-score">0</span></div>
        <div class="score-cell"><span class="score-cell-label">Best</span><span class="score-cell-value score-cell-value--muted" id="game-best">0</span></div>
        <div class="score-cell"><span class="score-cell-label">Time</span><span class="score-cell-value" id="game-time">10.0</span></div>
      </section>

      <section class="tap-arena">
        <div class="tap-rings" id="tapRings" aria-hidden="true">
          <span class="ring ring-1"></span>
          <span class="ring ring-2"></span>
          <span class="ring ring-3"></span>
        </div>
        <button class="tap-core" id="tapCore"><span id="tapCoreLabel">TAP</span></button>
      </section>

      <div class="game-status" id="gameStatus">Tap the core to arm the session.</div>
      <button class="btn btn-primary" id="resetGameBtn">Reset session</button>
    </section>

    <!-- ---------- REFLEX TEST ---------- -->
    <section class="game-screen is-hidden" id="gameScreen-reaction" data-game-screen="reaction">
      <button class="back-btn" data-back-to-menu>← Games</button>

      <section class="panel game-scoreboard">
        <div class="score-cell"><span class="score-cell-label">Last</span><span class="score-cell-value" id="reaction-last">—</span></div>
        <div class="score-cell"><span class="score-cell-label">Best</span><span class="score-cell-value score-cell-value--muted" id="reaction-best">—</span></div>
        <div class="score-cell"><span class="score-cell-label">Tries</span><span class="score-cell-value" id="reaction-tries">0</span></div>
      </section>

      <section class="tap-arena">
        <button class="reflex-box" id="reflexBox">
          <span id="reflexBoxLabel">Tap to start</span>
        </button>
      </section>

      <div class="game-status" id="reactionStatus">Wait for the box to turn green, then tap it as fast as you can.</div>
    </section>

    <!-- ---------- MEMORY GRID ---------- -->
    <section class="game-screen is-hidden" id="gameScreen-memory" data-game-screen="memory">
      <button class="back-btn" data-back-to-menu>← Games</button>

      <section class="panel game-scoreboard">
        <div class="score-cell"><span class="score-cell-label">Moves</span><span class="score-cell-value" id="memory-moves">0</span></div>
        <div class="score-cell"><span class="score-cell-label">Best</span><span class="score-cell-value score-cell-value--muted" id="memory-best">—</span></div>
        <div class="score-cell"><span class="score-cell-label">Pairs</span><span class="score-cell-value" id="memory-pairs">0/6</span></div>
      </section>

      <div class="memory-grid" id="memoryGrid"></div>

      <div class="game-status" id="memoryStatus">Flip two cards to find a matching pair.</div>
      <button class="btn btn-primary" id="memoryRestartBtn">Shuffle &amp; restart</button>
    </section>

    <!-- ---------- QUICK QUIZ ---------- -->
    <section class="game-screen is-hidden" id="gameScreen-quiz" data-game-screen="quiz">
      <button class="back-btn" data-back-to-menu>← Games</button>

      <section class="panel game-scoreboard">
        <div class="score-cell"><span class="score-cell-label">Score</span><span class="score-cell-value" id="quiz-score">0</span></div>
        <div class="score-cell"><span class="score-cell-label">Best</span><span class="score-cell-value score-cell-value--muted" id="quiz-best">—</span></div>
        <div class="score-cell"><span class="score-cell-label">Question</span><span class="score-cell-value" id="quiz-progress">1/6</span></div>
      </section>

      <section class="panel quiz-panel">
        <p class="quiz-question" id="quizQuestion">—</p>
        <div class="quiz-options" id="quizOptions"></div>
      </section>

      <div class="game-status" id="quizStatus">Pick an answer to continue.</div>
      <button class="btn btn-primary is-hidden" id="quizRestartBtn">Play again</button>
    </section>

  </main>

  <!-- ===================== VIEW: MEDIA ===================== -->
  <main class="view is-hidden" id="view-media" data-view="media">

    <section class="panel player-panel">
      <div class="panel-head"><span class="panel-eyebrow">Now Playing</span></div>

      <div class="cover-frame">
        <div class="cover-glow"></div>
        <div class="cover-disc" id="coverDisc"><div class="cover-disc-hole"></div></div>
      </div>

      <h2 class="track-title" id="trackTitle">—</h2>
      <p class="track-sub" id="trackArtist">—</p>

      <canvas id="waveCanvas" class="wave-canvas" height="64"></canvas>

      <div class="scrub-row">
        <span class="time-label" id="timeElapsed">0:00</span>
        <input type="range" id="scrubBar" class="scrub-bar" min="0" max="1000" value="0" />
        <span class="time-label" id="timeTotal">0:00</span>
      </div>

      <div class="transport-row">
        <button class="transport-btn" id="prevBtn" aria-label="Previous track">
          <svg viewBox="0 0 24 24"><path d="M6 6h2v12H6zM20 6v12l-8.5-6z"/></svg>
        </button>
        <button class="transport-btn transport-btn--main" id="playBtn" aria-label="Play">
          <svg id="playIcon" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        </button>
        <button class="transport-btn" id="nextBtn" aria-label="Next track">
          <svg viewBox="0 0 24 24"><path d="M16 6h2v12h-2zM4 6l8.5 6L4 18z"/></svg>
        </button>
      </div>

      <div class="volume-row">
        <svg class="vol-icon" viewBox="0 0 24 24"><path d="M4 9v6h4l5 5V4L8 9H4z"/></svg>
        <input type="range" id="volBar" class="scrub-bar scrub-bar--thin" min="0" max="100" value="70" />
      </div>
    </section>

    <section class="panel">
      <div class="panel-head"><span class="panel-eyebrow">Frequency</span></div>
      <ul class="track-list" id="trackList"></ul>
    </section>

    <audio id="audioEl" preload="metadata"></audio>

  </main>

  <!-- ===================== VIEW: STORE ===================== -->
  <main class="view is-hidden" id="view-store" data-view="store">

    <section class="panel store-hero">
      <span class="panel-eyebrow">Premium Access</span>
      <h2 class="store-title">Arcade Pass</h2>
      <p class="hint-text">Unlocks <strong>Code Breaker</strong> and <strong>Word Signal</strong> permanently on this account.</p>
      <div class="store-price-row">
        <span class="store-price">⭐ <?= PREMIUM_PRICE_STARS ?></span>
        <span class="store-price-label">Telegram Stars</span>
      </div>
      <button class="btn btn-primary" id="unlockPremiumBtn">Unlock with Telegram Stars</button>
      <button class="btn btn-outline is-hidden" id="devSimulateBtn">Simulate purchase (dev/testing only)</button>
      <p class="store-status" id="storeStatus"></p>
    </section>

    <section class="panel">
      <div class="panel-head"><span class="panel-eyebrow">How it works</span></div>
      <dl class="id-grid">
        <div class="id-row"><dt>Currency</dt><dd>Telegram Stars (XTR)</dd></div>
        <div class="id-row"><dt>Provider</dt><dd>Telegram native payments</dd></div>
        <div class="id-row"><dt>Where it works</dt><dd>Inside Telegram only</dd></div>
      </dl>
      <p class="hint-text" style="margin-top:10px;">Stars purchases open Telegram's own payment sheet — this app never sees or touches card details. A server-side webhook confirms the charge before anything unlocks for keeps.</p>
    </section>

  </main>

  <!-- ===================== BOTTOM NAV ===================== -->
  <nav class="bottom-nav">
    <button class="nav-btn is-active" data-target="home">
      <svg viewBox="0 0 24 24"><path d="M12 3l9 8h-3v9h-5v-6h-2v6H6v-9H3z"/></svg>
      <span>Home</span>
    </button>
    <button class="nav-btn" data-target="game">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.4" fill="var(--bg-deep)" stroke="none"/></svg>
      <span>Game</span>
    </button>
    <button class="nav-btn" data-target="media">
      <svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <span>Media</span>
    </button>
    <button class="nav-btn" data-target="store">
      <svg viewBox="0 0 24 24"><path d="M12 2l2.4 6.6L21 9l-5 4.4L17.4 21 12 17.3 6.6 21 8 13.4 3 9l6.6-0.4z"/></svg>
      <span>Store</span>
    </button>
  </nav>

</div>

<script>
window.PREMIUM_PRICE_STARS = <?= PREMIUM_PRICE_STARS ?>;
</script>

<script>
/* Config handed over from PHP to the client. */
window.SERVER_CONFIG = {
  verifyEndpoint: '?action=verify',
  invoiceEndpoint: '?action=create_invoice',
  serverConfigured: <?= $isConfigured ? 'true' : 'false' ?>
};
</script>

<script>
'use strict';

/* =========================================================
   1. TELEGRAM WEBAPP SDK BOOTSTRAP
   ========================================================= */

const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;

const Haptic = {
  tap(style) { if (tg && tg.HapticFeedback) tg.HapticFeedback.impactOccurred(style || 'light'); },
  notify(type) { if (tg && tg.HapticFeedback) tg.HapticFeedback.notificationOccurred(type || 'success'); },
  select() { if (tg && tg.HapticFeedback) tg.HapticFeedback.selectionChanged(); }
};

function initTelegram() {
  const connDot = document.getElementById('connDot');
  const connLabel = document.getElementById('connLabel');

  if (!tg) {
    connLabel.textContent = 'BROWSER';
    setServerCheckBadge('pending', 'NO CLIENT');
    return;
  }

  tg.ready();
  tg.expand();

  try {
    tg.setHeaderColor('#05070f');
    tg.setBackgroundColor('#05070f');
  } catch (e) { /* older clients may not support these calls */ }

  if (typeof tg.disableVerticalSwipes === 'function') tg.disableVerticalSwipes();

  connDot.classList.add('is-live');
  connLabel.textContent = (tg.platform || 'TELEGRAM').toUpperCase();

  if (tg.BackButton) {
    tg.BackButton.onClick(() => setView('home'));
  }

  verifyInitDataOnServer();
}

function syncBackButton(view) {
  if (!tg || !tg.BackButton) return;
  if (view === 'home') tg.BackButton.hide();
  else tg.BackButton.show();
}

/* Ask the PHP endpoint to HMAC-check the initData we were handed. */
async function verifyInitDataOnServer() {
  if (!window.SERVER_CONFIG.serverConfigured) {
    setServerCheckBadge('pending', 'NOT CONFIGURED');
    return;
  }
  if (!tg.initData) {
    setServerCheckBadge('pending', 'NO DATA');
    return;
  }
  try {
    const res = await fetch(window.SERVER_CONFIG.verifyEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ initData: tg.initData })
    });
    const result = await res.json();
    setServerCheckBadge(result.ok ? 'ok' : 'fail', result.ok ? 'VERIFIED' : 'FAILED');
  } catch (e) {
    setServerCheckBadge('fail', 'ERROR');
  }
}

function setServerCheckBadge(state, label) {
  const cell = document.getElementById('stat-serververify');
  cell.innerHTML = `<span class="badge badge--${state}">${label}</span>`;
}

/* =========================================================
   2. HOME — USER DATA
   ========================================================= */

function renderUser() {
  const user = tg && tg.initDataUnsafe ? tg.initDataUnsafe.user : null;

  const nameEl = document.getElementById('userName');
  const avatarImg = document.getElementById('userAvatar');
  const avatarFallback = document.getElementById('userInitial');

  const fullName = user ? [user.first_name, user.last_name].filter(Boolean).join(' ') : 'Guest Operator';
  nameEl.textContent = fullName;

  if (user && user.photo_url) {
    avatarImg.src = user.photo_url;
    avatarFallback.textContent = '';
  } else {
    avatarFallback.textContent = (fullName.trim()[0] || '?').toUpperCase();
  }

  setText('stat-userid', user ? user.id : '—');
  setText('stat-username', user && user.username ? '@' + user.username : '—');
  setText('stat-lang', (user && user.language_code ? user.language_code : (tg && tg.language_code) || '—').toUpperCase());
  setText('stat-premium', user ? (user.is_premium ? 'Yes' : 'No') : '—');
  setText('stat-platform', tg ? (tg.platform || '—') : '—');
  setText('stat-colorscheme', tg ? (tg.colorScheme || '—') : '—');

  const rawEl = document.getElementById('raw-initdata');
  rawEl.textContent = tg && tg.initData ? tg.initData : 'No initData — app was opened outside Telegram.';
}

function setText(id, value) { document.getElementById(id).textContent = value; }

/* =========================================================
   3. NAVIGATION
   ========================================================= */

function setView(target) {
  document.querySelectorAll('.view').forEach((el) => {
    el.classList.toggle('is-hidden', el.dataset.view !== target);
  });
  document.querySelectorAll('.nav-btn').forEach((btn) => {
    btn.classList.toggle('is-active', btn.dataset.target === target);
  });
  syncBackButton(target);
  Haptic.select();
}

function initNav() {
  document.querySelectorAll('.nav-btn').forEach((btn) => {
    btn.addEventListener('click', () => setView(btn.dataset.target));
  });
  document.getElementById('goToGameBtn').addEventListener('click', () => setView('game'));
}

/* =========================================================
   4. TAP-TO-SCORE GAME
   ========================================================= */

const GAME_DURATION = 10;
const BEST_SCORE_KEY = 'signal_best_score';

const game = {
  score: 0,
  best: Number(localStorage.getItem(BEST_SCORE_KEY)) || 0,
  timeLeft: GAME_DURATION,
  running: false,
  timerId: null
};

function initGame() {
  const core = document.getElementById('tapCore');
  const resetBtn = document.getElementById('resetGameBtn');
  renderGame();
  core.addEventListener('click', onTap);
  resetBtn.addEventListener('click', resetGame);
}

function onTap() {
  if (game.timeLeft <= 0 && game.running === false && game.score > 0) return;
  if (!game.running) startRound();
  game.score += 1;
  Haptic.tap('medium');
  pulseCore();
  renderGame();
}

function startRound() {
  game.running = true;
  game.score = 0;
  game.timeLeft = GAME_DURATION;
  document.getElementById('tapCore').classList.remove('is-done');
  setStatus('Session armed — keep tapping!');

  game.timerId = setInterval(() => {
    game.timeLeft = Math.max(0, +(game.timeLeft - 0.1).toFixed(1));
    renderGame();
    if (game.timeLeft <= 0) endRound();
  }, 100);
}

function endRound() {
  clearInterval(game.timerId);
  game.running = false;
  document.getElementById('tapCore').classList.add('is-done');

  const improved = game.score > game.best;
  if (improved) {
    game.best = game.score;
    localStorage.setItem(BEST_SCORE_KEY, String(game.best));
    Haptic.notify('success');
    setStatus(`New best — ${game.score} taps! Reset to go again.`);
  } else {
    Haptic.notify('warning');
    setStatus(`Time's up — ${game.score} taps. Reset to go again.`);
  }

  renderGame();
  renderHomeBest();
}

function resetGame() {
  clearInterval(game.timerId);
  game.running = false;
  game.score = 0;
  game.timeLeft = GAME_DURATION;
  document.getElementById('tapCore').classList.remove('is-done');
  setStatus('Tap the core to arm the session.');
  renderGame();
}

function pulseCore() {
  const core = document.getElementById('tapCore');
  core.classList.remove('is-pulsing');
  void core.offsetWidth;
  core.classList.add('is-pulsing');
}

function setStatus(text) { document.getElementById('gameStatus').textContent = text; }

function renderGame() {
  setText('game-score', game.score);
  setText('game-best', game.best);
  setText('game-time', game.timeLeft.toFixed(1));
}

function renderHomeBest() {
  setText('home-best-score', game.best);
  setText('menu-best-tap', 'Best: ' + game.best);
}

function renderMenuBestBadges() {
  setText('menu-best-codebreaker', 'Best: ' + (cb.best !== null ? cb.best : '—'));
  setText('menu-best-wordsignal', 'Best: ' + (ws.best !== null ? ws.best + '/' + WS_WORDS.length : '—'));
}

/* =========================================================
   4b. GAME HUB NAVIGATION
   ========================================================= */

const PREMIUM_KEY = 'signal_premium_unlocked';
const PREMIUM_GAMES = ['codebreaker', 'wordsignal'];

function isPremiumUnlocked() {
  return localStorage.getItem(PREMIUM_KEY) === '1';
}

function initGameHub() {
  document.querySelectorAll('[data-open-game]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (PREMIUM_GAMES.includes(btn.dataset.openGame) && !isPremiumUnlocked()) {
        Haptic.notify('warning');
        setView('store');
        return;
      }
      openGame(btn.dataset.openGame);
    });
  });
  document.querySelectorAll('[data-back-to-menu]').forEach((btn) => {
    btn.addEventListener('click', () => openGame('menu'));
  });
  refreshPremiumBadges();
}

function refreshPremiumBadges() {
  const unlocked = isPremiumUnlocked();
  PREMIUM_GAMES.forEach((name) => {
    const badge = document.getElementById('lockbadge-' + name);
    if (!badge) return;
    badge.textContent = unlocked ? '✓ Unlocked' : '🔒 Premium';
    badge.classList.toggle('is-unlocked', unlocked);
  });
}

function openGame(name) {
  document.querySelectorAll('.game-screen').forEach((el) => {
    el.classList.toggle('is-hidden', el.dataset.gameScreen !== name);
  });
  Haptic.select();
}

/* =========================================================
   4c. REFLEX TEST
   ========================================================= */

const REACTION_BEST_KEY = 'signal_best_reaction_ms';

const reaction = {
  state: 'idle', // idle | waiting | armed | toosoon | result
  best: Number(localStorage.getItem(REACTION_BEST_KEY)) || null,
  tries: 0,
  armedAt: 0,
  timeoutId: null
};

function initReaction() {
  const box = document.getElementById('reflexBox');
  box.addEventListener('click', onReflexTap);
  renderReaction();
}

function onReflexTap() {
  const box = document.getElementById('reflexBox');
  const label = document.getElementById('reflexBoxLabel');

  if (reaction.state === 'idle' || reaction.state === 'result') {
    reaction.state = 'waiting';
    box.className = 'reflex-box is-waiting';
    label.textContent = 'Wait for green…';
    document.getElementById('reactionStatus').textContent = 'Don\u2019t tap yet — wait for it to turn green.';
    const delay = 1200 + Math.random() * 2200;
    reaction.timeoutId = setTimeout(() => {
      reaction.state = 'armed';
      reaction.armedAt = performance.now();
      box.className = 'reflex-box is-armed';
      label.textContent = 'TAP NOW';
    }, delay);
    return;
  }

  if (reaction.state === 'waiting') {
    clearTimeout(reaction.timeoutId);
    reaction.state = 'toosoon';
    box.className = 'reflex-box is-toosoon';
    label.textContent = 'Too soon!';
    Haptic.notify('error');
    document.getElementById('reactionStatus').textContent = 'Jumped the gun — tap the box to try again.';
    setTimeout(() => { reaction.state = 'result'; }, 100);
    return;
  }

  if (reaction.state === 'armed') {
    const ms = Math.round(performance.now() - reaction.armedAt);
    reaction.state = 'result';
    reaction.tries += 1;
    box.className = 'reflex-box';
    label.textContent = ms + ' ms';

    const improved = reaction.best === null || ms < reaction.best;
    if (improved) {
      reaction.best = ms;
      localStorage.setItem(REACTION_BEST_KEY, String(ms));
      Haptic.notify('success');
      document.getElementById('reactionStatus').textContent = `New best — ${ms}ms! Tap to try again.`;
    } else {
      Haptic.tap('light');
      document.getElementById('reactionStatus').textContent = `${ms}ms — tap to try again.`;
    }
    renderReaction();
  }
}

function renderReaction() {
  setText('reaction-last', document.getElementById('reflexBoxLabel').textContent.includes('ms')
    ? document.getElementById('reflexBoxLabel').textContent : '—');
  setText('reaction-best', reaction.best !== null ? reaction.best + ' ms' : '—');
  setText('reaction-tries', reaction.tries);
  setText('menu-best-reaction', 'Best: ' + (reaction.best !== null ? reaction.best + 'ms' : '—'));
}

/* =========================================================
   4d. MEMORY GRID
   ========================================================= */

const MEMORY_BEST_KEY = 'signal_best_memory_moves';
const MEMORY_SYMBOLS = ['◎', '⚡', '▦', '?', '◆', '☾'];

const memory = {
  cards: [],       // { symbol, matched, id }
  flipped: [],     // indices currently face-up (max 2)
  moves: 0,
  matchedPairs: 0,
  best: Number(localStorage.getItem(MEMORY_BEST_KEY)) || null,
  locked: false
};

function initMemory() {
  document.getElementById('memoryRestartBtn').addEventListener('click', startMemoryRound);
  startMemoryRound();
}

function startMemoryRound() {
  const deck = [...MEMORY_SYMBOLS, ...MEMORY_SYMBOLS]
    .map((symbol, i) => ({ symbol, matched: false, id: i }))
    .sort(() => Math.random() - 0.5);

  memory.cards = deck;
  memory.flipped = [];
  memory.moves = 0;
  memory.matchedPairs = 0;
  memory.locked = false;

  renderMemoryGrid();
  renderMemoryStats();
  document.getElementById('memoryStatus').textContent = 'Flip two cards to find a matching pair.';
}

function renderMemoryGrid() {
  const grid = document.getElementById('memoryGrid');
  grid.innerHTML = '';
  memory.cards.forEach((card, i) => {
    const el = document.createElement('button');
    el.className = 'memory-card';
    el.dataset.index = String(i);
    el.innerHTML = `<span class="memory-card-face">${'·'}</span>`;
    el.addEventListener('click', () => onMemoryFlip(i));
    grid.appendChild(el);
  });
}

function onMemoryFlip(i) {
  if (memory.locked) return;
  const card = memory.cards[i];
  if (card.matched || memory.flipped.includes(i)) return;

  memory.flipped.push(i);
  updateMemoryCardFace(i, true);
  Haptic.select();

  if (memory.flipped.length === 2) {
    memory.moves += 1;
    memory.locked = true;
    const [a, b] = memory.flipped;
    const isMatch = memory.cards[a].symbol === memory.cards[b].symbol;

    setTimeout(() => {
      if (isMatch) {
        memory.cards[a].matched = true;
        memory.cards[b].matched = true;
        memory.matchedPairs += 1;
        document.querySelector(`.memory-card[data-index="${a}"]`).classList.add('is-matched');
        document.querySelector(`.memory-card[data-index="${b}"]`).classList.add('is-matched');
        Haptic.notify('success');
      } else {
        updateMemoryCardFace(a, false);
        updateMemoryCardFace(b, false);
        Haptic.tap('light');
      }
      memory.flipped = [];
      memory.locked = false;
      renderMemoryStats();

      if (memory.matchedPairs === MEMORY_SYMBOLS.length) {
        finishMemoryRound();
      }
    }, 550);
  }

  renderMemoryStats();
}

function updateMemoryCardFace(i, faceUp) {
  const el = document.querySelector(`.memory-card[data-index="${i}"]`);
  if (!el) return;
  el.classList.toggle('is-flipped', faceUp);
  el.querySelector('.memory-card-face').textContent = faceUp ? memory.cards[i].symbol : '·';
}

function finishMemoryRound() {
  const improved = memory.best === null || memory.moves < memory.best;
  if (improved) {
    memory.best = memory.moves;
    localStorage.setItem(MEMORY_BEST_KEY, String(memory.moves));
    document.getElementById('memoryStatus').textContent = `Cleared in a new best — ${memory.moves} moves!`;
    Haptic.notify('success');
  } else {
    document.getElementById('memoryStatus').textContent = `Cleared in ${memory.moves} moves. Shuffle to beat your best.`;
  }
  renderMemoryStats();
}

function renderMemoryStats() {
  setText('memory-moves', memory.moves);
  setText('memory-best', memory.best !== null ? memory.best : '—');
  setText('memory-pairs', memory.matchedPairs + '/' + MEMORY_SYMBOLS.length);
  setText('menu-best-memory', 'Best: ' + (memory.best !== null ? memory.best + ' moves' : '—'));
}

/* =========================================================
   4e. QUICK QUIZ
   ========================================================= */

const QUIZ_BEST_KEY = 'signal_best_quiz_score';

const QUIZ_QUESTIONS = [
  { q: 'Which protocol underlies most instant messaging apps like Telegram at its core?', options: ['MTProto', 'FTP', 'SMTP', 'SSH'], answer: 0 },
  { q: 'In radio, what does "signal-to-noise ratio" measure?', options: ['Battery drain', 'Clarity of a signal versus background interference', 'Antenna length', 'Frequency band'], answer: 1 },
  { q: 'What color do you get by mixing blue and red light?', options: ['Green', 'Yellow', 'Magenta', 'Cyan'], answer: 2 },
  { q: 'Which of these is a lossless audio format?', options: ['MP3', 'FLAC', 'AAC', 'OGG'], answer: 1 },
  { q: 'What does "HTTP" stand for?', options: ['HyperText Transfer Protocol', 'High Transfer Text Protocol', 'HyperText Terminal Process', 'Host Transfer Text Protocol'], answer: 0 },
  { q: 'On a standard analog clock, how many degrees does the minute hand sweep in 15 minutes?', options: ['45°', '60°', '90°', '120°'], answer: 2 }
];

const quiz = {
  index: 0,
  score: 0,
  best: Number(localStorage.getItem(QUIZ_BEST_KEY)) || null,
  answered: false
};

function initQuiz() {
  document.getElementById('quizRestartBtn').addEventListener('click', startQuizRound);
  startQuizRound();
}

function startQuizRound() {
  quiz.index = 0;
  quiz.score = 0;
  quiz.answered = false;
  document.getElementById('quizRestartBtn').classList.add('is-hidden');
  renderQuizQuestion();
}

function renderQuizQuestion() {
  const q = QUIZ_QUESTIONS[quiz.index];
  setText('quizQuestion', q.q);
  setText('quiz-progress', (quiz.index + 1) + '/' + QUIZ_QUESTIONS.length);
  setText('quiz-score', quiz.score);
  setText('quiz-best', quiz.best !== null ? quiz.best : '—');
  document.getElementById('quizStatus').textContent = 'Pick an answer to continue.';
  quiz.answered = false;

  const wrap = document.getElementById('quizOptions');
  wrap.innerHTML = '';
  q.options.forEach((opt, i) => {
    const btn = document.createElement('button');
    btn.className = 'quiz-option';
    btn.textContent = opt;
    btn.addEventListener('click', () => onQuizAnswer(i));
    wrap.appendChild(btn);
  });
}

function onQuizAnswer(i) {
  if (quiz.answered) return;
  quiz.answered = true;

  const q = QUIZ_QUESTIONS[quiz.index];
  const buttons = document.querySelectorAll('#quizOptions .quiz-option');
  buttons.forEach((btn, idx) => {
    btn.classList.add('is-disabled');
    if (idx === q.answer) btn.classList.add('is-correct');
    else if (idx === i) btn.classList.add('is-wrong');
  });

  if (i === q.answer) {
    quiz.score += 1;
    Haptic.notify('success');
    document.getElementById('quizStatus').textContent = 'Correct!';
  } else {
    Haptic.notify('error');
    document.getElementById('quizStatus').textContent = 'Not quite.';
  }
  setText('quiz-score', quiz.score);

  setTimeout(() => {
    if (quiz.index < QUIZ_QUESTIONS.length - 1) {
      quiz.index += 1;
      renderQuizQuestion();
    } else {
      finishQuizRound();
    }
  }, 900);
}

function finishQuizRound() {
  const improved = quiz.best === null || quiz.score > quiz.best;
  if (improved) {
    quiz.best = quiz.score;
    localStorage.setItem(QUIZ_BEST_KEY, String(quiz.score));
  }
  setText('quiz-best', quiz.best);
  setText('menu-best-quiz', 'Best: ' + quiz.best + '/' + QUIZ_QUESTIONS.length);
  document.getElementById('quizQuestion').textContent = `Round complete — ${quiz.score}/${QUIZ_QUESTIONS.length} correct.`;
  document.getElementById('quizOptions').innerHTML = '';
  document.getElementById('quizStatus').textContent = improved ? 'New best score!' : 'Play again to beat your best.';
  document.getElementById('quizRestartBtn').classList.remove('is-hidden');
}

/* =========================================================
   4f. STORE — Telegram Stars purchase flow
   ========================================================= */

function initStore() {
  const unlockBtn = document.getElementById('unlockPremiumBtn');
  const devBtn = document.getElementById('devSimulateBtn');

  unlockBtn.addEventListener('click', startStarsPurchase);

  // Real Stars payments only work inside an actual Telegram client.
  // Outside Telegram (plain browser testing) offer a clearly-labelled
  // dev shortcut instead of pretending to charge anything.
  if (!tg || typeof tg.openInvoice !== 'function') {
    devBtn.classList.remove('is-hidden');
    devBtn.addEventListener('click', () => {
      localStorage.setItem(PREMIUM_KEY, '1');
      refreshPremiumBadges();
      setStoreStatus('Premium unlocked locally for testing (no real payment made).');
    });
  }

  refreshStoreUI();
}

function refreshStoreUI() {
  const unlockBtn = document.getElementById('unlockPremiumBtn');
  if (isPremiumUnlocked()) {
    unlockBtn.textContent = 'Already unlocked ✓';
    unlockBtn.disabled = true;
  }
}

function setStoreStatus(text) {
  document.getElementById('storeStatus').textContent = text;
}

async function startStarsPurchase() {
  if (isPremiumUnlocked()) return;

  if (!tg || typeof tg.openInvoice !== 'function') {
    setStoreStatus('Open this Mini App inside Telegram to pay with Stars.');
    return;
  }

  setStoreStatus('Requesting invoice…');
  try {
    const res = await fetch(window.SERVER_CONFIG.invoiceEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ item: 'premium_pack' })
    });
    const result = await res.json();

    if (!result.ok) {
      setStoreStatus(result.reason === 'server_not_configured'
        ? 'Server has no bot token configured yet — Stars payments are disabled.'
        : 'Could not create invoice: ' + (result.reason || 'unknown error'));
      return;
    }

    setStoreStatus('Opening Telegram payment sheet…');
    tg.openInvoice(result.link, (status) => {
      if (status === 'paid') {
        // Client-side signal only — the webhook's successful_payment event
        // is the trustworthy confirmation for anything that matters server-side.
        localStorage.setItem(PREMIUM_KEY, '1');
        refreshPremiumBadges();
        refreshStoreUI();
        Haptic.notify('success');
        setStoreStatus('Payment received — Premium unlocked!');
      } else if (status === 'cancelled') {
        setStoreStatus('Payment cancelled.');
      } else {
        setStoreStatus('Payment ' + status + '.');
      }
    });
  } catch (e) {
    setStoreStatus('Network error creating invoice.');
  }
}

/* =========================================================
   4g. CODE BREAKER (Mastermind-style)
   ========================================================= */

const CB_BEST_KEY = 'signal_best_codebreaker';
const CB_CODE_LENGTH = 4;
const CB_DIGIT_MAX = 7; // digits 0-7
const CB_MAX_GUESSES = 8;

const cb = {
  secret: [],
  current: [],
  guesses: 0,
  best: Number(localStorage.getItem(CB_BEST_KEY)) || null,
  done: false
};

function initCodeBreaker() {
  document.getElementById('cbSubmitBtn').addEventListener('click', submitCbGuess);
  document.getElementById('cbRestartBtn').addEventListener('click', startCbRound);
  buildCbKeypad();
  startCbRound();
}

function startCbRound() {
  cb.secret = Array.from({ length: CB_CODE_LENGTH }, () => Math.floor(Math.random() * (CB_DIGIT_MAX + 1)));
  cb.current = [];
  cb.guesses = 0;
  cb.done = false;
  document.getElementById('cbHistory').innerHTML = '';
  document.getElementById('cbRestartBtn').classList.add('is-hidden');
  document.getElementById('cbSubmitBtn').classList.remove('is-hidden');
  setText('cb-best', cb.best !== null ? cb.best : '—');
  document.getElementById('cbStatus').textContent = 'Build a 4-digit guess, then submit.';
  renderCbInput();
}

function buildCbKeypad() {
  const pad = document.getElementById('cbKeypad');
  pad.innerHTML = '';
  for (let d = 0; d <= CB_DIGIT_MAX; d++) {
    const btn = document.createElement('button');
    btn.className = 'cb-key';
    btn.textContent = String(d);
    btn.addEventListener('click', () => {
      if (cb.done || cb.current.length >= CB_CODE_LENGTH) return;
      cb.current.push(d);
      Haptic.select();
      renderCbInput();
    });
    pad.appendChild(btn);
  }
  const clearBtn = document.createElement('button');
  clearBtn.className = 'cb-key';
  clearBtn.textContent = '⌫';
  clearBtn.addEventListener('click', () => {
    cb.current.pop();
    renderCbInput();
  });
  pad.appendChild(clearBtn);
}

function renderCbInput() {
  const row = document.getElementById('cbInputRow');
  row.innerHTML = '';
  for (let i = 0; i < CB_CODE_LENGTH; i++) {
    const slot = document.createElement('div');
    slot.className = 'cb-digit-slot';
    slot.textContent = cb.current[i] !== undefined ? cb.current[i] : '';
    row.appendChild(slot);
  }
  setText('cb-guesses', cb.guesses + '/' + CB_MAX_GUESSES);
}

function submitCbGuess() {
  if (cb.done || cb.current.length !== CB_CODE_LENGTH) {
    document.getElementById('cbStatus').textContent = 'Fill all 4 digits first.';
    return;
  }

  cb.guesses += 1;
  const { exact, partial } = scoreCbGuess(cb.current, cb.secret);
  appendCbHistoryRow(cb.current, exact, partial);

  if (exact === CB_CODE_LENGTH) {
    cb.done = true;
    const improved = cb.best === null || cb.guesses < cb.best;
    if (improved) {
      cb.best = cb.guesses;
      localStorage.setItem(CB_BEST_KEY, String(cb.guesses));
      setText('menu-best-codebreaker', 'Best: ' + cb.guesses);
      Haptic.notify('success');
      document.getElementById('cbStatus').textContent = `Cracked it in a new best — ${cb.guesses} guesses!`;
    } else {
      Haptic.notify('success');
      document.getElementById('cbStatus').textContent = `Cracked it in ${cb.guesses} guesses!`;
    }
    finishCbRound();
  } else if (cb.guesses >= CB_MAX_GUESSES) {
    cb.done = true;
    Haptic.notify('error');
    document.getElementById('cbStatus').textContent = `Out of guesses — the code was ${cb.secret.join('')}.`;
    finishCbRound();
  } else {
    Haptic.tap('light');
    document.getElementById('cbStatus').textContent = `${exact} exact, ${partial} partial. Keep going.`;
  }

  cb.current = [];
  renderCbInput();
}

function scoreCbGuess(guess, secret) {
  let exact = 0;
  const remainingSecret = [];
  const remainingGuess = [];

  for (let i = 0; i < CB_CODE_LENGTH; i++) {
    if (guess[i] === secret[i]) exact += 1;
    else {
      remainingSecret.push(secret[i]);
      remainingGuess.push(guess[i]);
    }
  }

  let partial = 0;
  remainingGuess.forEach((digit) => {
    const idx = remainingSecret.indexOf(digit);
    if (idx !== -1) {
      partial += 1;
      remainingSecret.splice(idx, 1);
    }
  });

  return { exact, partial };
}

function appendCbHistoryRow(guess, exact, partial) {
  const history = document.getElementById('cbHistory');
  const row = document.createElement('div');
  row.className = 'cb-history-row';
  row.innerHTML = `<span>${guess.join(' ')}</span><span class="cb-history-pegs">${'●'.repeat(exact)}${'○'.repeat(partial)}</span>`;
  history.prepend(row);
}

function finishCbRound() {
  document.getElementById('cbSubmitBtn').classList.add('is-hidden');
  document.getElementById('cbRestartBtn').classList.remove('is-hidden');
}

/* =========================================================
   4h. WORD SIGNAL (word unscramble)
   ========================================================= */

const WS_BEST_KEY = 'signal_best_wordsignal';

const WS_WORDS = [
  { word: 'SIGNAL', hint: 'What this whole app is named after' },
  { word: 'STATIC', hint: 'Unwanted noise on a radio channel' },
  { word: 'CIRCUIT', hint: 'A closed electrical loop' },
  { word: 'ANTENNA', hint: 'Device that sends or receives radio waves' },
  { word: 'FREQUENCY', hint: 'How often a wave repeats per second' },
  { word: 'DECIBEL', hint: 'Unit used to measure sound or signal level' },
  { word: 'BANDWIDTH', hint: 'Range of frequencies a channel can carry' },
  { word: 'ENCRYPT', hint: 'To scramble data so only the intended party can read it' }
];

const ws = {
  index: 0,
  score: 0,
  best: Number(localStorage.getItem(WS_BEST_KEY)) || null,
  answer: [],   // letters currently placed in the answer slots
  letters: []   // shuffled letter pool for the current word
};

function initWordSignal() {
  document.getElementById('wsClearBtn').addEventListener('click', clearWsAnswer);
  document.getElementById('wsSubmitBtn').addEventListener('click', submitWsAnswer);
  document.getElementById('wsRestartBtn').addEventListener('click', startWsRound);
  startWsRound();
}

function startWsRound() {
  ws.index = 0;
  ws.score = 0;
  setText('ws-best', ws.best !== null ? ws.best : '—');
  document.getElementById('wsClearBtn').classList.remove('is-hidden');
  document.getElementById('wsSubmitBtn').classList.remove('is-hidden');
  document.getElementById('wsRestartBtn').classList.add('is-hidden');
  loadWsWord();
}

function loadWsWord() {
  const entry = WS_WORDS[ws.index];
  ws.answer = [];
  ws.letters = entry.word.split('').map((ch, i) => ({ ch, id: i, used: false }));
  shuffleArray(ws.letters);

  setText('wsHint', entry.hint);
  setText('ws-score', ws.score);
  setText('ws-progress', (ws.index + 1) + '/' + WS_WORDS.length);
  document.getElementById('wsStatus').textContent = 'Tap letters to spell the word.';
  renderWsSlots(entry.word.length);
  renderWsTiles();
}

function shuffleArray(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
}

function renderWsSlots(length) {
  const wrap = document.getElementById('wsAnswerSlots');
  wrap.innerHTML = '';
  for (let i = 0; i < length; i++) {
    const slot = document.createElement('div');
    slot.className = 'ws-slot';
    slot.textContent = ws.answer[i] ? ws.answer[i].ch : '';
    wrap.appendChild(slot);
  }
}

function renderWsTiles() {
  const wrap = document.getElementById('wsLetterTiles');
  wrap.innerHTML = '';
  ws.letters.forEach((tile) => {
    const btn = document.createElement('button');
    btn.className = 'ws-tile' + (tile.used ? ' is-used' : '');
    btn.textContent = tile.ch;
    btn.addEventListener('click', () => onWsTileTap(tile));
    wrap.appendChild(btn);
  });
}

function onWsTileTap(tile) {
  if (tile.used || ws.answer.length >= ws.letters.length) return;
  tile.used = true;
  ws.answer.push(tile);
  Haptic.select();
  renderWsSlots(ws.letters.length);
  renderWsTiles();
}

function clearWsAnswer() {
  ws.answer = [];
  ws.letters.forEach((t) => (t.used = false));
  renderWsSlots(ws.letters.length);
  renderWsTiles();
}

function submitWsAnswer() {
  const guess = ws.answer.map((t) => t.ch).join('');
  const target = WS_WORDS[ws.index].word;

  if (guess.length !== target.length) {
    document.getElementById('wsStatus').textContent = 'Use all the letters first.';
    return;
  }

  if (guess === target) {
    ws.score += 1;
    Haptic.notify('success');
    document.getElementById('wsStatus').textContent = 'Correct!';
  } else {
    Haptic.notify('error');
    document.getElementById('wsStatus').textContent = `Not quite — it was ${target}.`;
  }
  setText('ws-score', ws.score);

  setTimeout(() => {
    if (ws.index < WS_WORDS.length - 1) {
      ws.index += 1;
      loadWsWord();
    } else {
      finishWsRound();
    }
  }, 900);
}

function finishWsRound() {
  const improved = ws.best === null || ws.score > ws.best;
  if (improved) {
    ws.best = ws.score;
    localStorage.setItem(WS_BEST_KEY, String(ws.score));
    setText('menu-best-wordsignal', 'Best: ' + ws.score + '/' + WS_WORDS.length);
  }
  setText('ws-best', ws.best);
  document.getElementById('wsHint').textContent = `Round complete — ${ws.score}/${WS_WORDS.length} correct.`;
  document.getElementById('wsAnswerSlots').innerHTML = '';
  document.getElementById('wsLetterTiles').innerHTML = '';
  document.getElementById('wsStatus').textContent = improved ? 'New best score!' : 'Play again to beat your best.';
  document.getElementById('wsClearBtn').classList.add('is-hidden');
  document.getElementById('wsSubmitBtn').classList.add('is-hidden');
  document.getElementById('wsRestartBtn').classList.remove('is-hidden');
}

/* =========================================================
   5. MEDIA PLAYER — procedurally generated demo tracks
   ========================================================= */

const PLAYLIST = [
  { name: 'Night Frequency', artist: 'Signal Deck', duration: 14, notes: [220, 261.6, 329.6, 392], tempo: 0.42 },
  { name: 'Red Static', artist: 'Signal Deck', duration: 12, notes: [196, 233.1, 293.7, 349.2], tempo: 0.3 },
  { name: 'Blue Hour', artist: 'Signal Deck', duration: 16, notes: [174.6, 220, 261.6, 329.6], tempo: 0.5 },
  { name: 'Carrier Wave', artist: 'Signal Deck', duration: 13, notes: [246.9, 293.7, 369.9, 440], tempo: 0.36 }
];

const media = { index: 0, buffers: [], audioEl: null, waveCtx: null };
let _wasPlaying = false;

async function initMedia() {
  media.audioEl = document.getElementById('audioEl');
  media.waveCtx = document.getElementById('waveCanvas').getContext('2d');

  buildTrackList();
  wireTransportControls();

  for (const track of PLAYLIST) {
    const buffer = await renderTrackBuffer(track);
    media.buffers.push(buffer);
  }

  loadTrack(0, { autoplay: false });
}

function buildTrackList() {
  const list = document.getElementById('trackList');
  list.innerHTML = '';
  PLAYLIST.forEach((track, i) => {
    const li = document.createElement('li');
    li.className = 'track-item';
    li.dataset.index = String(i);
    li.innerHTML = `
      <span class="track-item-idx">${String(i + 1).padStart(2, '0')}</span>
      <span class="track-item-body">
        <div class="track-item-name">${track.name}</div>
        <div class="track-item-artist">${track.artist}</div>
      </span>
      <span class="track-item-dur">${formatTime(track.duration)}</span>
    `;
    li.addEventListener('click', () => loadTrack(i, { autoplay: true }));
    list.appendChild(li);
  });
}

function wireTransportControls() {
  document.getElementById('playBtn').addEventListener('click', togglePlay);
  document.getElementById('prevBtn').addEventListener('click', () => step(-1));
  document.getElementById('nextBtn').addEventListener('click', () => step(1));

  const scrub = document.getElementById('scrubBar');
  scrub.addEventListener('input', () => {
    const pct = scrub.value / 1000;
    media.audioEl.currentTime = pct * (media.audioEl.duration || 0);
    updateScrubFill(scrub, pct * 100);
  });

  const vol = document.getElementById('volBar');
  vol.addEventListener('input', () => {
    media.audioEl.volume = vol.value / 100;
    updateScrubFill(vol, vol.value);
  });
  media.audioEl.volume = vol.value / 100;
  updateScrubFill(vol, vol.value);

  media.audioEl.addEventListener('timeupdate', onTimeUpdate);
  media.audioEl.addEventListener('ended', () => step(1));
}

function updateScrubFill(input, pct) { input.style.setProperty('--fill', pct + '%'); }

function loadTrack(index, opts) {
  media.index = (index + PLAYLIST.length) % PLAYLIST.length;
  const track = PLAYLIST[media.index];
  const buffer = media.buffers[media.index];

  if (buffer) {
    const blob = audioBufferToWavBlob(buffer);
    const url = URL.createObjectURL(blob);
    media.audioEl.src = url;
  }

  setText('trackTitle', track.name);
  setText('trackArtist', track.artist);
  document.querySelectorAll('.track-item').forEach((el) => {
    el.classList.toggle('is-active', Number(el.dataset.index) === media.index);
  });

  drawWaveform(buffer);
  setPlayIcon(false);
  document.getElementById('coverDisc').classList.remove('is-spinning');

  if (opts && opts.autoplay) {
    media.audioEl.play().then(() => setPlayIcon(true));
    document.getElementById('coverDisc').classList.add('is-spinning');
  }
  Haptic.select();
}

function step(dir) {
  loadTrack(media.index + dir, { autoplay: !media.audioEl.paused || _wasPlaying });
}

function togglePlay() {
  if (media.audioEl.paused) {
    media.audioEl.play();
    setPlayIcon(true);
    document.getElementById('coverDisc').classList.add('is-spinning');
    _wasPlaying = true;
  } else {
    media.audioEl.pause();
    setPlayIcon(false);
    document.getElementById('coverDisc').classList.remove('is-spinning');
    _wasPlaying = false;
  }
  Haptic.tap('light');
}

function setPlayIcon(isPlaying) {
  const icon = document.getElementById('playIcon');
  icon.innerHTML = isPlaying
    ? '<path d="M7 5h4v14H7zM13 5h4v14h-4z"/>'
    : '<path d="M8 5v14l11-7z"/>';
}

function onTimeUpdate() {
  const el = media.audioEl;
  if (!el.duration) return;
  const pct = (el.currentTime / el.duration) * 1000;
  document.getElementById('scrubBar').value = String(pct);
  updateScrubFill(document.getElementById('scrubBar'), pct / 10);
  setText('timeElapsed', formatTime(el.currentTime));
  setText('timeTotal', formatTime(el.duration));
  drawWaveform(media.buffers[media.index], el.currentTime / el.duration);
}

function formatTime(sec) {
  sec = Math.max(0, Math.floor(sec || 0));
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

function drawWaveform(buffer, progress) {
  const canvas = media.waveCtx.canvas;
  const ctx = media.waveCtx;
  const w = (canvas.width = canvas.clientWidth * devicePixelRatio);
  const h = (canvas.height = canvas.clientHeight * devicePixelRatio);
  ctx.clearRect(0, 0, w, h);
  if (!buffer) return;

  const data = buffer.getChannelData(0);
  const barCount = 64;
  const step = Math.floor(data.length / barCount);
  const mid = h / 2;
  const barWidth = w / barCount;
  const playedBars = Math.floor((progress || 0) * barCount);

  for (let i = 0; i < barCount; i++) {
    let sum = 0;
    for (let j = 0; j < step; j++) sum += Math.abs(data[i * step + j] || 0);
    const amp = (sum / step) * h * 3.2;
    const barH = Math.max(2, Math.min(h * 0.9, amp));
    ctx.fillStyle = i <= playedBars ? '#ff3b4e' : 'rgba(140,160,220,0.28)';
    ctx.fillRect(i * barWidth + barWidth * 0.25, mid - barH / 2, barWidth * 0.5, barH);
  }
}

function renderTrackBuffer(track) {
  const sampleRate = 22050;
  const length = sampleRate * track.duration;
  const ctxOffline = new OfflineAudioContext(1, length, sampleRate);

  const master = ctxOffline.createGain();
  master.gain.value = 0.25;
  master.connect(ctxOffline.destination);

  const noteLen = track.tempo;
  let t = 0;
  let i = 0;
  while (t < track.duration) {
    const freq = track.notes[i % track.notes.length];
    const osc = ctxOffline.createOscillator();
    osc.type = i % 2 === 0 ? 'sine' : 'triangle';
    osc.frequency.value = freq;

    const env = ctxOffline.createGain();
    env.gain.setValueAtTime(0, t);
    env.gain.linearRampToValueAtTime(1, t + 0.02);
    env.gain.linearRampToValueAtTime(0, t + noteLen);

    osc.connect(env);
    env.connect(master);
    osc.start(t);
    osc.stop(t + noteLen + 0.02);

    t += noteLen;
    i += 1;
  }

  return ctxOffline.startRendering();
}

function audioBufferToWavBlob(buffer) {
  const numChannels = buffer.numberOfChannels;
  const sampleRate = buffer.sampleRate;
  const samples = buffer.getChannelData(0);
  const bytesPerSample = 2;
  const blockAlign = numChannels * bytesPerSample;
  const dataSize = samples.length * blockAlign;
  const bufferArr = new ArrayBuffer(44 + dataSize);
  const view = new DataView(bufferArr);

  writeStr(view, 0, 'RIFF');
  view.setUint32(4, 36 + dataSize, true);
  writeStr(view, 8, 'WAVE');
  writeStr(view, 12, 'fmt ');
  view.setUint32(16, 16, true);
  view.setUint16(20, 1, true);
  view.setUint16(22, numChannels, true);
  view.setUint32(24, sampleRate, true);
  view.setUint32(28, sampleRate * blockAlign, true);
  view.setUint16(32, blockAlign, true);
  view.setUint16(34, 16, true);
  writeStr(view, 36, 'data');
  view.setUint32(40, dataSize, true);

  let offset = 44;
  for (let i = 0; i < samples.length; i++) {
    const s = Math.max(-1, Math.min(1, samples[i]));
    view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7fff, true);
    offset += 2;
  }

  return new Blob([bufferArr], { type: 'audio/wav' });
}

function writeStr(view, offset, str) {
  for (let i = 0; i < str.length; i++) view.setUint8(offset + i, str.charCodeAt(i));
}

/* =========================================================
   6. BOOT
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
  initTelegram();
  renderUser();
  initNav();
  initGameHub();
  initGame();
  initReaction();
  initMemory();
  initQuiz();
  initCodeBreaker();
  initWordSignal();
  initStore();
  renderMenuBestBadges();
  renderHomeBest();
  initMedia().catch((err) => console.error('Media init failed:', err));
});
</script>
</body>
</html>
