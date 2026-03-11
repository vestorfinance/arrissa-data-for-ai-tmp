<?php
/**
 * Migration runner — called automatically during the update process.
 *
 * Behavior:
 *  1. Ensures a `migrations` tracking table exists in the SQLite DB.
 *  2. Runs `init.php` first (always safe — uses CREATE IF NOT EXISTS / INSERT OR IGNORE).
 *  3. Scans the database/ directory for files matching:
 *       create-*.php   migrate-*.php
 *  4. Runs each one that has NOT been recorded yet, in alphabetical order.
 *  5. Records successful runs so they are never repeated.
 *
 * Safe to re-run at any time.
 */

$dbPath = __DIR__ . '/app.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "[migrations] ERROR: Cannot open database: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// ── Ensure migrations tracking table exists ──────────────────────────────────
$db->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        filename   TEXT    NOT NULL UNIQUE,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Always run init.php first (idempotent) ───────────────────────────────────
$initFile = __DIR__ . '/init.php';
if (file_exists($initFile)) {
    echo "[migrations] Running init.php ... ";
    try {
        require $initFile;
        echo "OK" . PHP_EOL;
    } catch (Throwable $e) {
        echo "FAILED: " . $e->getMessage() . PHP_EOL;
        // init failure is non-fatal for subsequent migrations
    }
}

// ── Collect migration files ───────────────────────────────────────────────────
$dir   = __DIR__;
$files = glob($dir . '/create-*.php');
$files = array_merge($files, glob($dir . '/migrate-*.php'));
sort($files); // alphabetical = chronological if names include dates/numbers

if (empty($files)) {
    echo "[migrations] No create-* or migrate-* files found." . PHP_EOL;
    exit(0);
}

// ── Already-applied filenames ─────────────────────────────────────────────────
$applied = [];
foreach ($db->query("SELECT filename FROM migrations") as $row) {
    $applied[] = $row['filename'];
}

// ── Run pending migrations ────────────────────────────────────────────────────
$ran     = 0;
$skipped = 0;

foreach ($files as $filePath) {
    $filename = basename($filePath);

    if (in_array($filename, $applied, true)) {
        echo "[migrations] SKIP  $filename (already applied)" . PHP_EOL;
        $skipped++;
        continue;
    }

    echo "[migrations] RUN   $filename ... ";
    try {
        require $filePath;

        // Record success
        $stmt = $db->prepare("INSERT OR IGNORE INTO migrations (filename) VALUES (?)");
        $stmt->execute([$filename]);

        echo "OK" . PHP_EOL;
        $ran++;
    } catch (Throwable $e) {
        echo "FAILED: " . $e->getMessage() . PHP_EOL;
        // Continue with remaining migrations rather than aborting everything
    }
}

echo "[migrations] Done — $ran applied, $skipped skipped." . PHP_EOL;
