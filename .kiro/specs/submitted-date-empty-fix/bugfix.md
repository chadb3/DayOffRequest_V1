# Bugfix Requirements Document

## Introduction

After the web-root refactor, new day-off requests show an empty Submitted date on the management page. The existing `data/Db3.db` was upgraded by `migrateSchema()` (src/db.php). SQLite's `ALTER TABLE ADD COLUMN` cannot use a non-constant default, so the migrated `EMPDAYOFFREQ.SUBMITTED_DATE` column is `TEXT NOT NULL DEFAULT ''`. Fresh databases built by `createSchema()` use `DEFAULT (datetime('now','localtime'))` and work.

Before task 9.2, `public/dayOffRequest.php` inserted an explicit `datetime('now','localtime')` value. Task 9.2 moved the insert into `insertRequest()`, which omits `SUBMITTED_DATE` and relies on the column default. On a migrated database that default is `''`, so new requests are stored with an empty `SUBMITTED_DATE`, and the archive copies the empty value. The tests did not catch this because `DbTestCase` always builds a fresh schema with `createSchema()`, never a migrated one.

Bug condition and property:

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type InsertRequestCall (database, request fields)
  OUTPUT: boolean

  // The table was produced by migrateSchema() from the legacy schema,
  // so SUBMITTED_DATE has DEFAULT ''
  RETURN X.database.EMPDAYOFFREQ.SUBMITTED_DATE.default = ''
END FUNCTION
```

```pascal
// Property: Fix Checking - Submitted date is set on every insert
FOR ALL X WHERE isBugCondition(X) DO
  result ← insertRequest'(X)
  row ← read(result.NUM)
  ASSERT row.SUBMITTED_DATE matches 'YYYY-MM-DD HH:MM:SS'
     AND row.SUBMITTED_DATE = current local date-time at insert (within test tolerance)
END FOR
```

```pascal
// Property: Preservation Checking
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT insertRequest(X) = insertRequest'(X)
END FOR
```

Counterexample: `insertRequest()` against the migrated `data/Db3.db` stores `SUBMITTED_DATE = ''`.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN a request is inserted via `insertRequest()` into a database whose `EMPDAYOFFREQ` table was produced by `migrateSchema()` (SUBMITTED_DATE default `''`) THEN the system stores an empty `SUBMITTED_DATE` for the new row.

1.2 WHEN the management page lists a request that was inserted into a migrated database after the refactor THEN the system shows an empty Submitted date.

1.3 WHEN a request with an empty `SUBMITTED_DATE` is archived THEN the system copies the empty value into the archive table.

1.4 WHEN the test suite runs THEN the system only exercises databases built by `createSchema()`, so the empty-date defect on migrated databases goes undetected.

### Expected Behavior (Correct)

2.1 WHEN a request is inserted via `insertRequest()` into any database, including one produced by `migrateSchema()` THEN the system SHALL store `SUBMITTED_DATE` as the current local date-time in `YYYY-MM-DD HH:MM:SS` format, regardless of how the table was created.

2.2 WHEN the management page lists a request inserted after the fix THEN the system SHALL show its non-empty Submitted date.

2.3 WHEN a request inserted after the fix is archived THEN the system SHALL copy its real `SUBMITTED_DATE` into the archive.

2.4 WHEN the test suite runs THEN the system SHALL include tests that insert into a database migrated from the legacy schema by `migrateSchema()` and assert a non-empty, correctly formatted `SUBMITTED_DATE`. These tests SHALL use a temporary database and SHALL NOT read or modify `data/Db3.db`.

2.5 WHEN a request, in either the pending or the history view, has an empty (`''`) `SUBMITTED_DATE` THEN the management page SHALL display "Unknown" in the Submitted column. The fallback SHALL be implemented in the presentation code of `public/manageDays.php`, not in SQL.

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a request is inserted into a database built by `createSchema()` THEN the system SHALL CONTINUE TO store `SUBMITTED_DATE` as the current local date-time.

3.2 WHEN a request is inserted without an explicit status THEN the system SHALL CONTINUE TO set `STATUS` to `'Pending'` from the column default.

3.3 WHEN a request is inserted THEN the system SHALL CONTINUE TO store NAME, PHONE, REASON, SHIFTS and SUB exactly as submitted, using parameterized queries.

3.4 WHEN a request is archived or restored THEN the system SHALL CONTINUE TO preserve all fields and the transactional rollback behavior.

3.5 WHEN rows already exist with real `SUBMITTED_DATE` values (backfilled at migration time) THEN the system SHALL CONTINUE TO return those dates unchanged.

3.6 WHEN the fix is applied THEN the system SHALL CONTINUE TO keep engine-specific SQL (including date-time functions) only in `src/db.php` (the "Unknown" fallback is not SQL), per Requirement 12 of the day-off-request spec.

3.7 WHEN the test suite runs THEN the system SHALL CONTINUE TO leave `data/Db3.db` unmodified.

3.8 WHEN rows with an empty `SUBMITTED_DATE` already exist in `EMPDAYOFFREQ` or `EMPDAYOFFREQ_ARCHIVE` THEN the system SHALL CONTINUE TO store them unmodified, with no backfill or repair of the stored value.

3.9 WHEN a request has a non-empty `SUBMITTED_DATE` THEN the management page SHALL CONTINUE TO display it exactly as stored, HTML-escaped, in both the pending and history views.

### Recorded Decision

Rows already saved with an empty `SUBMITTED_DATE` since the refactor are NOT repaired in the database, because their true submit time is unknown. Instead, the management page displays "Unknown" for an empty `SUBMITTED_DATE`. The fallback is presentation logic in `public/manageDays.php`, not engine-specific SQL. The decision covers the empty string `''` only; whitespace-only values are out of scope.
