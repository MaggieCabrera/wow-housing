<?php
// Do not force a JSON header here; choose per-request so opening this URL in a browser
// (which typically accepts text/html) does not produce a MIME-type warning.

function sendJson($data) {
    header('Content-Type: application/json; charset=utf-8');
    // API responses should not be aggressively cached by clients to ensure freshness
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode($data);
}

function sendSmart($data) {
    // If the client prefers HTML (typical when opening in a browser tab), render a small HTML page
    // containing the JSON so devtools/network won't warn about 'application/json' used as a document.
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    $preferHtml = (stripos($accept, 'text/html') !== false) && !isset($_GET['rawjson']);
    if ($preferHtml) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>API JSON</title></head><body><pre>';
        echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo '</pre></body></html>';
        return;
    }
    // Default: JSON
    sendJson($data);
}

// end helpers

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}
$logFile = $dataDir . DIRECTORY_SEPARATOR . 'api_debug.log';

function api_log($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[{$time}] " . $msg . "\n", FILE_APPEND | LOCK_EX);
}

$overridesFile = $dataDir . DIRECTORY_SEPARATOR . 'housing_overrides_by_map.json';
$interestedFile = $dataDir . DIRECTORY_SEPARATOR . 'housing_interested_by_map.json';

$method = $_SERVER['REQUEST_METHOD'];

function read_file_json($path) {
    if (!file_exists($path)) return null;
    $txt = @file_get_contents($path);
    if ($txt === false) return null;
    $decoded = json_decode($txt, true);
    if ($decoded === null) return new stdClass();

    // If the stored JSON is a list/array (numeric indexed), convert to empty object
    // because the client expects an object mapping (mapFilename => { plotNumber: names })
    if (is_array($decoded)) {
        // check if array is a list (sequential numeric keys starting at 0)
        $keys = array_keys($decoded);
        $isList = true;
        foreach ($keys as $i => $k) {
            if ($k !== $i) { $isList = false; break; }
        }
        if ($isList) {
            return new stdClass();
        }
    }
    return $decoded;
}

if ($method === 'GET') {
    api_log('GET ' . ($_SERVER['QUERY_STRING'] ?? ''));
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'get_overrides') {
        $data = read_file_json($overridesFile);
        sendSmart($data);
        exit;
    } elseif ($action === 'get_interested') {
        $data = read_file_json($interestedFile);
        sendSmart($data);
        exit;
    } else {
        // default: return both
        $out = [
            'overrides' => read_file_json($overridesFile),
            'interested' => read_file_json($interestedFile)
        ];
        sendSmart($out);
        exit;
    }
} elseif ($method === 'POST') {
    $rawRequest = file_get_contents('php://input');
    api_log('POST body: ' . $rawRequest);
    // read raw body
    $body = $rawRequest;
    $data = json_decode($body, true);
    if (!$data || !isset($data['action'])) {
        http_response_code(400);
        $err = ['ok' => false, 'error' => 'Invalid request: missing action or malformed JSON'];
        api_log('ERROR: ' . json_encode($err));
        echo json_encode($err);
        exit;
    }
    $action = $data['action'];
    $payload = isset($data['payload']) ? $data['payload'] : null;

    if ($action === 'save_overrides') {
        if ($payload === null) {
            http_response_code(400);
            $err = ['ok' => false, 'error' => 'Missing payload'];
            api_log('ERROR: ' . json_encode($err));
            echo json_encode($err);
            exit;
        }
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $res = @file_put_contents($overridesFile, $json, LOCK_EX);
        if ($res === false) {
            $errMsg = error_get_last();
            api_log('WRITE ERROR overrides: ' . json_encode($errMsg));
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Failed to write overrides file', 'detail' => $errMsg]);
            exit;
        }
    api_log('Saved overrides to ' . $overridesFile);
    sendJson(['ok' => true]);
        exit;
    } elseif ($action === 'save_interested') {
        if ($payload === null) {
            http_response_code(400);
            $err = ['ok' => false, 'error' => 'Missing payload'];
            api_log('ERROR: ' . json_encode($err));
            echo json_encode($err);
            exit;
        }
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $res = @file_put_contents($interestedFile, $json, LOCK_EX);
        if ($res === false) {
            $errMsg = error_get_last();
            api_log('WRITE ERROR interested: ' . json_encode($errMsg));
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Failed to write interested file', 'detail' => $errMsg]);
            exit;
        }
    api_log('Saved interested to ' . $interestedFile);
    sendJson(['ok' => true]);
        exit;
    }

    http_response_code(400);
    $err = ['ok' => false, 'error' => 'Unknown action'];
    api_log('ERROR: ' . json_encode($err));
    sendJson($err);
    exit;
}
?>