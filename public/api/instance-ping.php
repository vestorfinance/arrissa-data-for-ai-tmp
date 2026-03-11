<?php
/**
 * Instance Ping — receives heartbeat stats from remote Arrissa Data instances.
 *
 * POST fields:
 *   network_token, instance_url, instance_name, php_version, os_platform,
 *   cpu_load, ram_total, ram_used, ram_pct, disk_total, disk_used, disk_pct,
 *   uptime_s, app_version
 *
 * Only the arrissadata.com host accepts and persists this data.
 * All instances share the same hardcoded network token — no manual config needed.
 */

// Shared network token — identical on all Arrissa Data instances.
// Acts as a proof-of-origin: only software running this codebase knows this value.
define('ARRISSA_NETWORK_TOKEN', 'arr_net_9c3f2a1e7b4d8f056e2a3c9d1b7e4f8a');

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Only accept on arrissadata.com
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($host !== 'arrissadata.com' && $host !== 'www.arrissadata.com') {
    http_response_code(403);
    echo json_encode(['error' => 'This endpoint is only active on the arrissadata.com hub']);
    exit;
}

// Verify network token
$submittedToken = $_POST['network_token'] ?? '';
if (!hash_equals(ARRISSA_NETWORK_TOKEN, $submittedToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

// Ensure table exists (idempotent)
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

// Validate required fields
$instanceUrl = trim($_POST['instance_url'] ?? '');
if (empty($instanceUrl) || !filter_var($instanceUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid instance_url is required']);
    exit;
}

$instanceUrl = rtrim($instanceUrl, '/');
$instanceKey = hash('sha256', strtolower($instanceUrl));

$cpu      = isset($_POST['cpu_load'])   && is_numeric($_POST['cpu_load'])   ? (float)$_POST['cpu_load']   : null;
$ramTotal = isset($_POST['ram_total'])  && is_numeric($_POST['ram_total'])  ? (int)$_POST['ram_total']    : null;
$ramUsed  = isset($_POST['ram_used'])   && is_numeric($_POST['ram_used'])   ? (int)$_POST['ram_used']     : null;
$ramPct   = isset($_POST['ram_pct'])    && is_numeric($_POST['ram_pct'])    ? (float)$_POST['ram_pct']    : null;
$dskTotal = isset($_POST['disk_total']) && is_numeric($_POST['disk_total']) ? (int)$_POST['disk_total']   : null;
$dskUsed  = isset($_POST['disk_used'])  && is_numeric($_POST['disk_used'])  ? (int)$_POST['disk_used']    : null;
$dskPct   = isset($_POST['disk_pct'])   && is_numeric($_POST['disk_pct'])   ? (float)$_POST['disk_pct']   : null;
$uptimeS  = isset($_POST['uptime_s'])   && is_numeric($_POST['uptime_s'])   ? (int)$_POST['uptime_s']     : null;

try {
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
    ", [
        ':instance_key'  => $instanceKey,
        ':instance_url'  => $instanceUrl,
        ':instance_name' => substr(trim($_POST['instance_name'] ?? $instanceUrl), 0, 100),
        ':php_version'   => substr(trim($_POST['php_version']   ?? ''), 0, 20),
        ':os_platform'   => substr(trim($_POST['os_platform']   ?? ''), 0, 50),
        ':cpu_load'      => $cpu,
        ':ram_total'     => $ramTotal,
        ':ram_used'      => $ramUsed,
        ':ram_pct'       => $ramPct,
        ':disk_total'    => $dskTotal,
        ':disk_used'     => $dskUsed,
        ':disk_pct'      => $dskPct,
        ':uptime_s'      => $uptimeS,
        ':app_version'   => substr(trim($_POST['app_version'] ?? ''), 0, 50),
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to store heartbeat data']);
}


header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Only accept on arrissadata.com
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($host !== 'arrissadata.com' && $host !== 'www.arrissadata.com') {
    http_response_code(403);
    echo json_encode(['error' => 'This endpoint is only active on the arrissadata.com hub']);
    exit;
}

require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

// Ensure table exists (idempotent)
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

// Verify shared secret
$setting = $db->fetchOne("SELECT value FROM settings WHERE key = 'stats_reporting_secret'");
$expectedSecret = $setting['value'] ?? null;

$submittedSecret = $_POST['secret'] ?? '';
if (!$expectedSecret || !$submittedSecret || !hash_equals($expectedSecret, $submittedSecret)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized — invalid or missing secret']);
    exit;
}

// Validate required fields
$instanceUrl = trim($_POST['instance_url'] ?? '');
if (empty($instanceUrl) || !filter_var($instanceUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid instance_url is required']);
    exit;
}

$instanceUrl = rtrim($instanceUrl, '/');
$instanceKey = hash('sha256', strtolower($instanceUrl));

$cpu      = isset($_POST['cpu_load'])   && is_numeric($_POST['cpu_load'])   ? (float)$_POST['cpu_load']   : null;
$ramTotal = isset($_POST['ram_total'])  && is_numeric($_POST['ram_total'])  ? (int)$_POST['ram_total']    : null;
$ramUsed  = isset($_POST['ram_used'])   && is_numeric($_POST['ram_used'])   ? (int)$_POST['ram_used']      : null;
$ramPct   = isset($_POST['ram_pct'])    && is_numeric($_POST['ram_pct'])    ? (float)$_POST['ram_pct']    : null;
$dskTotal = isset($_POST['disk_total']) && is_numeric($_POST['disk_total']) ? (int)$_POST['disk_total']   : null;
$dskUsed  = isset($_POST['disk_used'])  && is_numeric($_POST['disk_used'])  ? (int)$_POST['disk_used']    : null;
$dskPct   = isset($_POST['disk_pct'])   && is_numeric($_POST['disk_pct'])   ? (float)$_POST['disk_pct']   : null;
$uptimeS  = isset($_POST['uptime_s'])   && is_numeric($_POST['uptime_s'])   ? (int)$_POST['uptime_s']     : null;

try {
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
    ", [
        ':instance_key'  => $instanceKey,
        ':instance_url'  => $instanceUrl,
        ':instance_name' => substr(trim($_POST['instance_name'] ?? $instanceUrl), 0, 100),
        ':php_version'   => substr(trim($_POST['php_version']   ?? ''), 0, 20),
        ':os_platform'   => substr(trim($_POST['os_platform']   ?? ''), 0, 50),
        ':cpu_load'      => $cpu,
        ':ram_total'     => $ramTotal,
        ':ram_used'      => $ramUsed,
        ':ram_pct'       => $ramPct,
        ':disk_total'    => $dskTotal,
        ':disk_used'     => $dskUsed,
        ':disk_pct'      => $dskPct,
        ':uptime_s'      => $uptimeS,
        ':app_version'   => substr(trim($_POST['app_version'] ?? ''), 0, 50),
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to store heartbeat data']);
}
