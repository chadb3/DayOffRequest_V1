<?php

// define() instead of const: dirname() is not allowed in a const expression.
if (!defined('DEFAULT_DB_PATH')) {
    define('DEFAULT_DB_PATH', dirname(__DIR__) . '/data/Db3.db');
}

/** Open a connection. $path may be ':memory:' (tests). */
function getDb(string $path = DEFAULT_DB_PATH): SQLite3 {
    $db = new SQLite3($path);
    $db->busyTimeout(5000);
    if ($path !== ':memory:') {
        $db->exec('PRAGMA journal_mode = WAL');
    }
    return $db;
}

/** Create EMPDAYOFFREQ and EMPDAYOFFREQ_ARCHIVE if missing. */
function createSchema(SQLite3 $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS EMPDAYOFFREQ (
        NUM INTEGER PRIMARY KEY AUTOINCREMENT,
        NAME TEXT NOT NULL,
        PHONE TEXT NOT NULL,
        SHIFTS TEXT NOT NULL,
        SUB TEXT NOT NULL,
        REASON TEXT NOT NULL,
        STATUS TEXT NOT NULL DEFAULT 'Pending',
        SUBMITTED_DATE TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");
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
}

/** Insert a request; STATUS and SUBMITTED_DATE come from column defaults. Returns the new NUM. */
function insertRequest(SQLite3 $db, array $data): int {
    $stmt = $db->prepare(
        'INSERT INTO EMPDAYOFFREQ (NAME, PHONE, SHIFTS, SUB, REASON)
         VALUES (:name, :phone, :shifts, :sub, :reason)'
    );
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare insert: ' . $db->lastErrorMsg());
    }
    foreach (['name', 'phone', 'shifts', 'sub', 'reason'] as $field) {
        $stmt->bindValue(':' . $field, (string)($data[$field] ?? ''), SQLITE3_TEXT);
    }
    if ($stmt->execute() === false) {
        throw new RuntimeException('Failed to insert request: ' . $db->lastErrorMsg());
    }
    return $db->lastInsertRowID();
}

function getRequestById(SQLite3 $db, int $num): ?array {
    $stmt = $db->prepare('SELECT * FROM EMPDAYOFFREQ WHERE NUM = :n');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare select: ' . $db->lastErrorMsg());
    }
    $stmt->bindValue(':n', $num, SQLITE3_INTEGER);
    $result = $stmt->execute();
    if ($result === false) {
        throw new RuntimeException('Failed to read request: ' . $db->lastErrorMsg());
    }
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row === false ? null : $row;
}

/** Number of rows in the active requests table. */
function countRequests(SQLite3 $db): int {
    $count = $db->querySingle('SELECT COUNT(*) FROM EMPDAYOFFREQ');
    if ($count === false || $count === null) {
        throw new RuntimeException('Failed to count requests: ' . $db->lastErrorMsg());
    }
    return (int) $count;
}

/** @return array<int, array<string,mixed>> rows with the given statuses, ordered by NUM */
function fetchRequestsByStatus(SQLite3 $db, array $statuses): array {
    $placeholders = [];
    foreach (array_values($statuses) as $i => $_) {
        $placeholders[] = ':s' . $i;
    }
    $stmt = $db->prepare(
        'SELECT * FROM EMPDAYOFFREQ WHERE STATUS IN (' . implode(', ', $placeholders) . ') ORDER BY NUM'
    );
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare select: ' . $db->lastErrorMsg());
    }
    foreach (array_values($statuses) as $i => $status) {
        $stmt->bindValue(':s' . $i, $status, SQLITE3_TEXT);
    }
    $result = $stmt->execute();
    if ($result === false) {
        throw new RuntimeException('Failed to read requests: ' . $db->lastErrorMsg());
    }
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

/** @return array<int, array<string,mixed>> rows with STATUS = 'Pending' */
function getPendingRequests(SQLite3 $db): array {
    return fetchRequestsByStatus($db, ['Pending']);
}

/** @return array<int, array<string,mixed>> rows with STATUS IN ('Approved','Denied') */
function getHistoryRequests(SQLite3 $db): array {
    return fetchRequestsByStatus($db, ['Approved', 'Denied']);
}

/** $status must be 'Approved' or 'Denied'. Returns true if a row changed. */
function updateRequestStatus(SQLite3 $db, int $num, string $status): bool {
    if (!in_array($status, ['Approved', 'Denied'], true)) {
        throw new InvalidArgumentException('Invalid status: ' . $status);
    }
    $stmt = $db->prepare("UPDATE EMPDAYOFFREQ SET STATUS = :status WHERE NUM = :n AND STATUS = 'Pending'");
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare update: ' . $db->lastErrorMsg());
    }
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':n', $num, SQLITE3_INTEGER);
    if ($stmt->execute() === false) {
        throw new RuntimeException('Failed to update status: ' . $db->lastErrorMsg());
    }
    return $db->changes() > 0;
}

/** Move an Approved/Denied row to the archive table in one transaction. Returns false and rolls back on failure. */
function archiveRequest(SQLite3 $db, int $num): bool {
    $db->exec('BEGIN TRANSACTION');
    try {
        $ins = $db->prepare(
            "INSERT INTO EMPDAYOFFREQ_ARCHIVE
               (NUM, NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE, ARCHIVED_DATE)
             SELECT NUM, NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE, datetime('now','localtime')
             FROM EMPDAYOFFREQ WHERE NUM = :n AND STATUS IN ('Approved','Denied')"
        );
        if ($ins === false) {
            throw new RuntimeException('Failed to prepare archive insert: ' . $db->lastErrorMsg());
        }
        $ins->bindValue(':n', $num, SQLITE3_INTEGER);
        if ($ins->execute() === false || $db->changes() !== 1) {
            throw new RuntimeException('Archive insert failed or request not archivable');
        }
        $del = $db->prepare('DELETE FROM EMPDAYOFFREQ WHERE NUM = :n');
        if ($del === false) {
            throw new RuntimeException('Failed to prepare archive delete: ' . $db->lastErrorMsg());
        }
        $del->bindValue(':n', $num, SQLITE3_INTEGER);
        if ($del->execute() === false || $db->changes() !== 1) {
            throw new RuntimeException('Archive delete failed');
        }
        $db->exec('COMMIT');
        return true;
    } catch (Throwable $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

function getArchivedRequestById(SQLite3 $db, int $num): ?array {
    $stmt = $db->prepare('SELECT * FROM EMPDAYOFFREQ_ARCHIVE WHERE NUM = :n');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare select: ' . $db->lastErrorMsg());
    }
    $stmt->bindValue(':n', $num, SQLITE3_INTEGER);
    $result = $stmt->execute();
    if ($result === false) {
        throw new RuntimeException('Failed to read archived request: ' . $db->lastErrorMsg());
    }
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row === false ? null : $row;
}

/**
 * Bring an existing database up to the current schema. Idempotent.
 * @return string[] progress messages, one per line
 */
function migrateSchema(SQLite3 $db): array {
    $messages = [];

    $columnExists = function (string $table, string $column) use ($db): bool {
        $result = $db->query("PRAGMA table_info($table)");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] === $column) {
                return true;
            }
        }
        return false;
    };

    $columns = [
        'SHIFTS' => "TEXT NOT NULL DEFAULT ''",
        'SUB' => "TEXT NOT NULL DEFAULT ''",
        'STATUS' => "TEXT NOT NULL DEFAULT 'Pending'",
        // ALTER TABLE cannot add a non-constant default, so backfill below.
        'SUBMITTED_DATE' => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($columns as $column => $definition) {
        if ($columnExists('EMPDAYOFFREQ', $column)) {
            $messages[] = "$column column already exists in EMPDAYOFFREQ table. Skipping.";
            continue;
        }
        $db->exec("ALTER TABLE EMPDAYOFFREQ ADD COLUMN $column $definition");
        if ($column === 'SUBMITTED_DATE') {
            $db->exec("UPDATE EMPDAYOFFREQ SET SUBMITTED_DATE = datetime('now','localtime') WHERE SUBMITTED_DATE = ''");
        }
        $messages[] = "Added $column column to EMPDAYOFFREQ table.";
    }

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
    $messages[] = 'EMPDAYOFFREQ_ARCHIVE table is ready.';

    return $messages;
}
