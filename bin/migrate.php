<?php
/**
 * Database migration script for the Day-Off Request System.
 *
 * Adds missing columns to EMPDAYOFFREQ and creates the EMPDAYOFFREQ_ARCHIVE
 * table. Safe to run multiple times (idempotent). All SQL lives in db.php.
 */

// CLI only: refuse to run if reached through a web server.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Forbidden: migrate.php can only be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../src/db.php';

$db = getDb();
foreach (migrateSchema($db) as $message) {
    echo $message . "\n";
}
$db->close();

echo "\nMigration complete.\n";
