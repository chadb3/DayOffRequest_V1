<?php
/**
 * Database migration script for the Day-Off Request System.
 * 
 * Adds STATUS and SUBMITTED_DATE columns to EMPDAYOFFREQ table,
 * and creates the EMPDAYOFFREQ_ARCHIVE table.
 * 
 * Safe to run multiple times (idempotent).
 */

require_once 'db.php';

$db = getDb();

// Helper: Check if a column exists in a table
function columnExists(SQLite3 $db, string $table, string $column): bool {
    $result = $db->query("PRAGMA table_info($table)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

// Add STATUS column if it doesn't exist
if (!columnExists($db, 'EMPDAYOFFREQ', 'STATUS')) {
    $db->exec("ALTER TABLE EMPDAYOFFREQ ADD COLUMN STATUS TEXT NOT NULL DEFAULT 'Pending'");
    echo "Added STATUS column to EMPDAYOFFREQ table.\n";
} else {
    echo "STATUS column already exists in EMPDAYOFFREQ table. Skipping.\n";
}

// Add SUBMITTED_DATE column if it doesn't exist
if (!columnExists($db, 'EMPDAYOFFREQ', 'SUBMITTED_DATE')) {
    $db->exec("ALTER TABLE EMPDAYOFFREQ ADD COLUMN SUBMITTED_DATE TEXT NOT NULL DEFAULT (datetime('now','localtime'))");
    echo "Added SUBMITTED_DATE column to EMPDAYOFFREQ table.\n";
} else {
    echo "SUBMITTED_DATE column already exists in EMPDAYOFFREQ table. Skipping.\n";
}

// Create EMPDAYOFFREQ_ARCHIVE table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS EMPDAYOFFREQ_ARCHIVE (
    NUM INTEGER PRIMARY KEY,
    NAME TEXT NOT NULL,
    PHONE TEXT NOT NULL,
    SHIFTS TEXT NOT NULL,
    SUB TEXT NOT NULL,
    REASON TEXT NOT NULL,
    STATUS TEXT NOT NULL,
    SUBMITTED_DATE TEXT NOT NULL,
    ARCHIVED_DATE TEXT NOT NULL DEFAULT (datetime('now','localtime'))
)");
echo "EMPDAYOFFREQ_ARCHIVE table is ready.\n";

echo "\nMigration complete.\n";
