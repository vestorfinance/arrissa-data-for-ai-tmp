<?php
/**
 * Migration: create the instance_heartbeats table and stats_reporting_secret setting.
 * Safe to re-run (IF NOT EXISTS / INSERT OR IGNORE throughout).
 */

$dbPath = __DIR__ . '/app.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("
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

    // Generate a strong secret if one doesn't exist yet
    $secret = bin2hex(random_bytes(32));
    $db->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('stats_reporting_secret', '$secret')");

    echo "Migration complete. instance_heartbeats table ready.\n";

    $row = $db->query("SELECT value FROM settings WHERE key = 'stats_reporting_secret'")->fetch(PDO::FETCH_ASSOC);
    echo "Reporting secret: " . ($row['value'] ?? '(not set)') . "\n";
    echo "Add this to each instance's .env file:\n";
    echo "STATS_REPORTING_SECRET=" . ($row['value'] ?? '') . "\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
