<?php

require_once __DIR__ . '/DbTestCase.php';

/**
 * Base class: fresh in-memory database built from the LEGACY schema, then upgraded with
 * migrateSchema(). Mirrors the shape of data/Db3.db. Never touches Db3.db.
 *
 * Legacy columns verified from git history (commit a2780d4, Db3.db sqlite_master) and
 * from PRAGMA table_info on the current data/Db3.db (read-only):
 *   NUM INTEGER PRIMARY KEY AUTOINCREMENT, NAME VARCHAR(255) NOT NULL,
 *   PHONE BLOB, REASON VARCHAR(255)
 * After migration: STATUS TEXT NOT NULL DEFAULT 'Pending', SHIFTS/SUB/SUBMITTED_DATE
 * TEXT NOT NULL DEFAULT ''.
 */
abstract class MigratedDbTestCase extends DbTestCase
{
    protected function setUp(): void
    {
        // Skip DbTestCase::setUp() (it builds the fresh schema); replicate its db setup.
        \PHPUnit\Framework\TestCase::setUp();
        $this->db = getDb(':memory:');
        $this->createLegacyTable($this->db);
        migrateSchema($this->db);
    }

    protected function createLegacyTable(SQLite3 $db): void
    {
        $db->exec('CREATE TABLE EMPDAYOFFREQ(
            NUM INTEGER PRIMARY KEY AUTOINCREMENT,
            NAME VARCHAR(255) NOT NULL,
            PHONE BLOB,
            REASON VARCHAR(255))');
    }

    /** Seconds between two "Y-m-d H:i:s" strings, parsed as plain UTC (no tz conversion). */
    protected static function secondsBetween(string $a, string $b): int
    {
        $ta = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $a, new DateTimeZone('UTC'));
        $tb = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $b, new DateTimeZone('UTC'));
        return abs($ta->getTimestamp() - $tb->getTimestamp());
    }
}
