<?php
/**
 * Database migration script for the Day-Off Request System.
 *
 * Adds missing columns to EMPDAYOFFREQ and creates the EMPDAYOFFREQ_ARCHIVE
 * table. Safe to run multiple times (idempotent). All SQL lives in db.php.
 */

require_once 'db.php';

$db = getDb();
foreach (migrateSchema($db) as $message) {
    echo $message . "\n";
}
$db->close();

echo "\nMigration complete.\n";
