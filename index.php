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
        <span class="best-score-label">taps logged</span>
      </div>
      <button class="btn btn-outline" id="goToGameBtn">Open the tap console →</button>
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
  </nav>

</div>

<script>
/* Config handed over from PHP to the client. */
window.SERVER_CONFIG = {
  verifyEndpoint: '?action=verify',
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

function renderHomeBest() { setText('home-best-score', game.best); }

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
  initGame();
  renderHomeBest();
  initMedia().catch((err) => console.error('Media init failed:', err));
});
</script>
</body>
</html>
