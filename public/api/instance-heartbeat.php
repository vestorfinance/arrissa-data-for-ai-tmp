<?php
/**
 * Instance Heartbeat Proxy
 *
 * Called via JS fetch from any authenticated dashboard page.
 * Collects local system stats and:
 *   – If running ON arrissadata.com → writes directly to the local DB.
 *   – If running on another instance → POSTs to https://arrissadata.com/api/instance-ping.
 *
 * Server-side rate-limit: at most once per 5 minutes (lock file).
 * JS-side rate-limit is also applied by the calling code in the layout.
 */

header('Content-Type: application/json');

// Server-side rate limit — max one report per 5 minutes
$lockFile = sys_get_temp_dir() . '/arrissa_hb_' . md5(__DIR__) . '.lock';
$lastSent = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
if (time() - $lastSent < 300) {
    echo json_encode(['skipped' => true, 'next_in' => 300 - (time() - $lastSent)]);
    exit;
}
file_put_contents($lockFile, time());

// Load .env
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

$secret = getenv('STATS_REPORTING_SECRET');
if (!$secret) {
    echo json_encode(['skipped' => true, 'reason' => 'STATS_REPORTING_SECRET not set in .env']);
    exit;
}

require_once __DIR__ . '/../../app/Database.php';
$db = Database::getInstance();

$appName = ($db->fetchOne("SELECT value FROM settings WHERE key = 'app_name'"))['value']  ?? 'Arrissa Data API';
$baseUrl = ($db->fetchOne("SELECT value FROM settings WHERE key = 'app_base_url'"))['value'] ?? '';

if (empty($baseUrl)) {
    echo json_encode(['skipped' => true, 'reason' => 'app_base_url not configured in Settings']);
    exit;
}

// ── Collect system stats ──────────────────────────────────────────────────────
$isLinux  = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
$loadAvg  = $isLinux ? sys_getloadavg() : false;
$cpu      = $loadAvg !== false ? round($loadAvg[0], 2) : null;

$ramTotal = $ramUsed = null;
if ($isLinux && is_readable('/proc/meminfo')) {
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma);
    if ($mt && $ma) {
        $ramTotal = (int)$mt[1] * 1024;
        $ramUsed  = $ramTotal - (int)$ma[1] * 1024;
    }
}

$diskPath  = __DIR__ . '/../../';
$diskTotal = @disk_total_space($diskPath) ?: null;
$diskFree  = @disk_free_space($diskPath)  ?: null;
$diskUsed  = ($diskTotal && $diskFree) ? $diskTotal - $diskFree : null;

$uptimeS = null;
if ($isLinux && is_readable('/proc/uptime')) {
    $uptimeS = (int)explode(' ', file_get_contents('/proc/uptime'))[0];
}

// Detect git commit short hash as app version
$appVersion = null;
$gitHead = __DIR__ . '/../../.git/HEAD';
if (file_exists($gitHead)) {
    $ref = trim(file_get_contents($gitHead));
    if (strpos($ref, 'ref: ') === 0) {
        $refFile = __DIR__ . '/../../.git/' . substr($ref, 5);
        if (file_exists($refFile)) {
            $appVersion = substr(trim(file_get_contents($refFile)), 0, 12);
        }
    } else {
        $appVersion = substr($ref, 0, 12);
    }
}

$instanceUrl = rtrim($baseUrl, '/');
$payload = [
    ':instance_key'  => hash('sha256', strtolower($instanceUrl)),
    ':instance_url'  => $instanceUrl,
    ':instance_name' => $appName,
    ':php_version'   => PHP_VERSION,
    ':os_platform'   => PHP_OS,
    ':cpu_load'      => $cpu,
    ':ram_total'     => $ramTotal,
    ':ram_used'      => $ramUsed,
    ':ram_pct'       => ($ramTotal && $ramUsed) ? round($ramUsed / $ramTotal * 100, 1) : null,
    ':disk_total'    => $diskTotal,
    ':disk_used'     => $diskUsed,
    ':disk_pct'      => ($diskTotal && $diskUsed) ? round($diskUsed / $diskTotal * 100, 1) : null,
    ':uptime_s'      => $uptimeS,
    ':app_version'   => $appVersion,
];

$currentHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$isHub = ($currentHost === 'arrissadata.com' || $currentHost === 'www.arrissadata.com');

if ($isHub) {
    // ── Running on the hub — write directly to local DB ──────────────────────
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS instance_heartbeats (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                instance_key  TEXT    NOT NULL UNIQUE,
                instance_url  TEXT    NOT NULL,
                instance_name TEXT,
                php_version   TEXT,
                os_platform   TEXT,
                cpu_load      REAL,
                ram_total     INTEGER,
                ram_used      INTEGER,
                ram_pct       REAL,
                disk_total    INTEGER,
                disk_used     INTEGER,
                disk_pct      REAL,
                uptime_s      INTEGER,
                app_version   TEXT,
                first_seen    DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_seen     DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $db->query("
            INSERT INTO instance_heartbeats
                (instance_key, instance_url, instance_name, php_version, os_platform,
                 cpu_load, ram_total, ram_used, ram_pct, disk_total, disk_used, disk_pct,
                 uptime_s, app_version, last_seen)
            VALUES
                (:instance_key, :instance_url, :instance_name, :php_version, :os_platform,
                 :cpu_load, :ram_total, :ram_used, :ram_pct, :disk_total, :disk_used, :disk_pct,
                 :uptime_s, :app_version, datetime('now'))
            ON CONFLICT(instance_key) DO UPDATE SET
                instance_url  = excluded.instance_url,
                instance_name = excluded.instance_name,
                php_version   = excluded.php_version,
                os_platform   = excluded.os_platform,
                cpu_load      = excluded.cpu_load,
                ram_total     = excluded.ram_total,
                ram_used      = excluded.ram_used,
                ram_pct       = excluded.ram_pct,
                disk_total    = excluded.disk_total,
                disk_used     = excluded.disk_used,
                disk_pct      = excluded.disk_pct,
                uptime_s      = excluded.uptime_s,
                app_version   = excluded.app_version,
                last_seen     = datetime('now')
        ", $payload);

        echo json_encode(['success' => true, 'method' => 'local']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB write failed']);
    }

} else {
    // ── Remote instance — POST to the hub ────────────────────────────────────
    if (!function_exists('curl_init')) {
        echo json_encode(['skipped' => true, 'reason' => 'cURL not available']);
        exit;
    }

    // Build POST body (strip the colon-prefixed keys used for PDO)
    $post = ['secret' => $secret];
    foreach ($payload as $k => $v) {
        $post[ltrim($k, ':')] = $v;
    }

    $ch = curl_init('https://arrissadata.com/api/instance-ping');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['error' => true, 'curl_error' => $curlErr]);
    } else {
        echo json_encode(['success' => $httpCode === 200, 'http_code' => $httpCode, 'method' => 'remote']);
    }
}
