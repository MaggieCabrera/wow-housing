<?php
require_once __DIR__ . '/config.php';

// Helper: constant-time string compare
function hash_equals_safe($a, $b) {
    if (function_exists('hash_equals')) return hash_equals($a, $b);
    if (strlen($a) !== strlen($b)) return false;
    $res = 0;
    for ($i = 0; $i < strlen($a); $i++) $res |= ord($a[$i]) ^ ord($b[$i]);
    return $res === 0;
}

function make_cookie_value($secret) {
    global $SITE_COOKIE_SALT;
    $ts = time();
    $payload = base64_encode(json_encode(['ts' => $ts]));
    $h = hash_hmac('sha256', $payload, $secret . $SITE_COOKIE_SALT);
    return $payload . '.' . $h;
}

function verify_cookie($cookieVal, $secret) {
    global $SITE_COOKIE_SALT;
    if (!$cookieVal) return false;
    $parts = explode('.', $cookieVal);
    if (count($parts) !== 2) return false;
    $payload = $parts[0];
    $sig = $parts[1];
    $expected = hash_hmac('sha256', $payload, $secret . $SITE_COOKIE_SALT);
    if (!hash_equals_safe($expected, $sig)) return false;
    // Optionally check timestamp inside payload if you want expiry
    return true;
}

// Login handling
$method = $_SERVER['REQUEST_METHOD'];
$message = '';
if ($method === 'POST') {
    $posted = isset($_POST['secret']) ? trim($_POST['secret']) : '';
    if ($posted !== '' && hash_equals_safe($posted, $SITE_SECRET)) {
        // success: set cookie
        $cookieVal = make_cookie_value($SITE_SECRET);
        setcookie($SITE_COOKIE_NAME, $cookieVal, ($SITE_COOKIE_LIFETIME ? time() + $SITE_COOKIE_LIFETIME : 0), $SITE_COOKIE_PATH, '', $SITE_COOKIE_SECURE, true);
        // redirect to original page (if provided) - sanitize to avoid open redirect
        $redirRaw = isset($_GET['r']) ? $_GET['r'] : '';
        $redir = '/housing/';
        if ($redirRaw) {
            // allow only internal relative paths
            $u = parse_url($redirRaw);
            $path = isset($u['path']) ? $u['path'] : $redirRaw;
            // if the path starts with /housing, keep it; if it's just '/', use /housing/
            if (strpos($path, '/housing') === 0) {
                $redir = $path;
            } elseif ($path === '/' || $path === '') {
                $redir = '/housing/';
            } else {
                // prevent redirect to external or unrelated paths
                $redir = '/housing/';
            }
        }
        header('Location: ' . $redir);
        exit;
    } else {
        $message = 'Ungültiges Secret.';
    }
}

// Render a simple login form
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Authentication required</title>
<style>
body { font-family: Arial, sans-serif; background:#1a1a1a; color:#fff; display:flex; align-items:center; justify-content:center; height:100vh; }
.form { background: #111; padding:20px; border-radius:8px; box-shadow:0 8px 32px rgba(0,0,0,0.6); width:320px }
input[type="password"] { width:100%; padding:8px; margin:8px 0 12px 0; border-radius:4px; border:1px solid #333; background:#0d0d0d; color:#fff }
button { padding:8px 12px; border-radius:4px; border:none; background:#2a77ff; color:#fff }
.message { color:#f55; margin-bottom:8px }
</style>
</head>
<body>
<div class="form">
    <h3>Zugriff geschützt</h3>
    <?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="post">
        <label for="secret">Secret:</label>
        <input id="secret" name="secret" type="password" autocomplete="off" />
        <div style="text-align:right; margin-top:8px;"><button type="submit">Anmelden</button></div>
    </form>
</div>
</body>
</html>
<?php

?>