# Submitted Date Empty Bugfix Design

## Overview

New day-off requests inserted into the migrated `data/Db3.db` are stored with an empty `SUBMITTED_DATE`. `insertRequest()` in `src/db.php` omits the column and relies on the column default. `createSchema()` defines that default as `datetime('now','localtime')`. `migrateSchema()` adds the column with `ALTER TABLE ... ADD COLUMN`, which cannot take a non-constant default, so the migrated column has `DEFAULT ''`. The management page then shows a blank Submitted date, and archiving copies the blank.

The fix is to make `insertRequest()` set `SUBMITTED_DATE` explicitly in the INSERT with `datetime('now','localtime')`. The insert then no longer depends on the column default, so it works on fresh and migrated tables. The change touches one SQL string and one docblock in `src/db.php`. It needs no schema change and no data migration.

Tests will add a helper that builds an in-memory database with the legacy schema, runs `migrateSchema()` on it, and exercises `insertRequest()` against it. This closes the gap that let the bug through (`DbTestCase` only builds fresh schemas).

No code is changed by this document.

## Glossary

- **Bug_Condition (C)**: `insertRequest()` is called on a database whose `EMPDAYOFFREQ.SUBMITTED_DATE` column default is `''` (a table produced by `migrateSchema()` from the legacy schema).
- **Property (P)**: The inserted row's `SUBMITTED_DATE` is the current local date-time in `YYYY-MM-DD HH:MM:SS` format.
- **Preservation**: Behavior on fresh-schema databases, `STATUS` defaulting to `'Pending'`, field storage, archiving, existing row values, and the rule that engine-specific SQL stays in `src/db.php`.
- **insertRequest()**: Function in `src/db.php` that inserts a request and returns the new `NUM`.
- **createSchema()**: Function in `src/db.php` that builds fresh tables. `SUBMITTED_DATE` has `DEFAULT (datetime('now','localtime'))`.
- **migrateSchema()**: Function in `src/db.php` that upgrades a legacy database. It adds `SUBMITTED_DATE` with `DEFAULT ''` and backfills existing rows once.
- **Legacy schema**: `EMPDAYOFFREQ(NUM INTEGER PRIMARY KEY AUTOINCREMENT, NAME VARCHAR(255) NOT NULL, PHONE BLOB, REASON VARCHAR(255))`, the table shape before migration, inferred from the columns `migrateSchema()` adds (SHIFTS, SUB, STATUS, SUBMITTED_DATE).
- **Migrated schema**: The legacy table after `migrateSchema()`. The current `data/Db3.db` has `STATUS TEXT NOT NULL DEFAULT 'Pending'`, `SHIFTS TEXT NOT NULL DEFAULT ''`, `SUB TEXT NOT NULL DEFAULT ''`, `SUBMITTED_DATE TEXT NOT NULL DEFAULT ''`.

## Bug Details

### Bug Condition

The bug manifests when `insertRequest()` runs against a migrated table. The INSERT lists only NAME, PHONE, SHIFTS, SUB and REASON, so SQLite fills `SUBMITTED_DATE` from the column default. On a migrated table that default is the empty string, which satisfies `NOT NULL`, so no error is raised and the blank value is stored silently.

**Formal Specification:**
```
FUNCTION isBugCondition(X)
  INPUT: X of type InsertRequestCall (database, request fields)
  OUTPUT: boolean

  RETURN X.database.EMPDAYOFFREQ.SUBMITTED_DATE.default = ''
END FUNCTION
```

### Examples

- Migrated `data/Db3.db`: `insertRequest($db, [...valid fields...])` stores `SUBMITTED_DATE = ''`. Expected: `'2025-01-15 09:30:12'` (current local time).
- Management page for that row: Submitted column is blank. Expected: the date-time.
- `archiveRequest()` on that row after approval: the archive row has `SUBMITTED_DATE = ''`. Expected: the real date-time copied.
- Fresh in-memory schema from `createSchema()`: stores the correct date today. Must stay correct (not buggy, preserved).
- Existing migrated rows with a backfilled date: unchanged by the fix.

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Inserts into `createSchema()` databases still store the current local date-time.
- `STATUS` still comes from the column default `'Pending'`.
- NAME, PHONE, REASON, SHIFTS, SUB are stored exactly as submitted through bound parameters.
- Archive and restore keep all fields and the transactional rollback behavior.
- Rows already holding real `SUBMITTED_DATE` values are returned unchanged.
- Engine-specific SQL (`datetime()`, PRAGMA, SQLite3) stays only in `src/db.php`, so the layout unit test for `public/`, `src/` and `bin/` still passes.
- `data/Db3.db` is not read or modified by tests.

**Scope:**
All inputs where the column default is already a real date-time expression (fresh schema) are unaffected in outcome. Reads, status updates, archiving and validation code paths are not modified.

## Hypothesized Root Cause

The cause is already confirmed by reading the code, with one residual check.

1. **Default divergence between fresh and migrated schemas** (confirmed): `createSchema()` uses a non-constant default, while `migrateSchema()` must use `DEFAULT ''` because `ALTER TABLE ADD COLUMN` rejects non-constant defaults.
2. **INSERT relies on the default** (confirmed): task 9.2 moved the insert into `insertRequest()` and dropped the explicit `datetime('now','localtime')` that `public/dayOffRequest.php` previously supplied.
3. **Test blind spot** (confirmed): `DbTestCase::setUp()` always calls `createSchema()`, so no test runs against a migrated table.
4. **Misleading docblock** (confirmed): the `insertRequest()` docblock says `SUBMITTED_DATE` comes from the column default, which encouraged the omission.

Residual check: confirm via the exploratory test that the migrated in-memory table produces `''`, matching the `data/Db3.db` schema.

## Correctness Properties

Property 1: Bug Condition - Submitted date is set on every insert

_For any_ valid request inserted by `insertRequest()` into a database where the bug condition holds (migrated schema, `SUBMITTED_DATE` default `''`), the fixed function SHALL store `SUBMITTED_DATE` matching `/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/` and within a few seconds of SQLite's `datetime('now','localtime')` read from the same connection.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

Property 2: Preservation - Existing insert, status, field and archive behavior

_For any_ valid request where the bug condition does NOT hold (fresh `createSchema()` database), the fixed function SHALL produce the same result as the original function: `SUBMITTED_DATE` is a correctly formatted current local date-time, `STATUS` is `'Pending'`, and NAME, PHONE, REASON, SHIFTS and SUB equal the submitted values. For both schemas, archiving a row SHALL copy its non-empty `SUBMITTED_DATE` unchanged and preserve all other fields.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9**

## Fix Implementation

### Changes Required

**File**: `src/db.php`

**Function**: `insertRequest()`

**Specific Changes**:
1. **Explicit date in INSERT**: change the statement to
   `INSERT INTO EMPDAYOFFREQ (NAME, PHONE, SHIFTS, SUB, REASON, SUBMITTED_DATE) VALUES (:name, :phone, :shifts, :sub, :reason, datetime('now','localtime'))`.
   STATUS stays omitted so the `'Pending'` default still applies on both schemas.
2. **Docblock**: replace "STATUS and SUBMITTED_DATE come from column defaults" with text stating that STATUS comes from the column default and SUBMITTED_DATE is set explicitly because migrated tables have `DEFAULT ''`.
3. **No other changes**: `createSchema()`, `migrateSchema()`, `archiveRequest()` and the `public/` pages stay as they are.

### Alternatives Considered

| Option | Verdict | Reason |
|---|---|---|
| Explicit `datetime('now','localtime')` in INSERT (chosen) | Chosen | One-line change, works for both schemas, SQL stays in `src/db.php`, uses the same clock and timezone as the rest of the code, no data migration. |
| (a) Rebuild the migrated table in `migrateSchema()` with the correct default (create new, copy, drop, rename) | Defer | Fixes the root default divergence, but rewrites the live table and is riskier. Could be a later cleanup. |
| (b) PHP-side `date('Y-m-d H:i:s')` | Reject | Timezone depends on `php.ini`, so it can differ from SQLite `localtime` used for `ARCHIVED_DATE` and the migration backfill. |
| (c) Trigger that fills empty `SUBMITTED_DATE` after insert | Reject | Adds hidden schema objects, more engine-specific SQL, and needs to be created in migration and fresh paths. |

## Testing Strategy

### Validation Approach

First surface the bug on unfixed code using a migrated in-memory database, then verify the fix and preservation after the change. Tests never touch `data/Db3.db`.

**Test helper**: add a helper (for example `createMigratedDb()` in a new `tests/MigratedDbTestCase.php`, or a protected method on `DbTestCase`) that:
1. opens `getDb(':memory:')`,
2. creates the legacy table `EMPDAYOFFREQ(NUM INTEGER PRIMARY KEY AUTOINCREMENT, NAME VARCHAR(255) NOT NULL, PHONE BLOB, REASON VARCHAR(255))`,
3. optionally inserts a legacy row to exercise the one-time backfill,
4. calls `migrateSchema()`.

Before writing the helper, confirm the legacy column set against git history (for example the earlier migration script or earlier `Db3.db`). The shape above is inferred from the columns `migrateSchema()` adds.

### Exploratory Bug Condition Checking

**Goal**: Surface the counterexample before the fix and confirm the root cause.

**Test Plan**: Build the migrated in-memory database, call `insertRequest()`, read the row with `getRequestById()`, and assert the date is formatted. Run on UNFIXED code.

**Test Cases**:
1. **Migrated insert**: `SUBMITTED_DATE` must match the format (will fail on unfixed code, value is `''`).
2. **Migrated insert then archive**: archive row has a non-empty date (will fail on unfixed code).
3. **Default inspection**: `PRAGMA table_info(EMPDAYOFFREQ)` shows `dflt_value` of `''` for `SUBMITTED_DATE` in the migrated table (confirms the root cause).
4. **Fresh insert**: same assertion on the fresh schema (passes on unfixed code, baseline).

**Expected Counterexamples**:
- `SUBMITTED_DATE === ''` after insert into the migrated schema.
- Cause: the column default, not the INSERT parameters.

### Fix Checking

**Goal**: For all inputs where the bug condition holds, the fixed function stores a correct date.

**Pseudocode:**
```
FOR ALL X WHERE isBugCondition(X) DO
  num := insertRequest_fixed(X.database, X.fields)
  row := getRequestById(X.database, num)
  ASSERT row.SUBMITTED_DATE MATCHES /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/
  ASSERT |row.SUBMITTED_DATE - X.database.querySingle("SELECT datetime('now','localtime')")| <= tolerance
END FOR
```

Implemented as an Eris property (`limitTo(100)` or more) using the existing request generators from `InsertReadRoundTripPropertyTest`. The tolerance is a few seconds, compared via timestamps parsed in PHP as plain strings (no timezone conversion), so both values come from the same SQLite clock.

### Preservation Checking

**Goal**: For all inputs where the bug condition does NOT hold, behavior is unchanged.

**Pseudocode:**
```
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT insertRequest_original(X) = insertRequest_fixed(X)
END FOR
```

**Testing Approach**: Property-based testing, because it covers many generated field values (including quotes, unicode and SQL-like text) cheaply.

**Test Cases**:
1. **Fresh-schema insert**: date format and closeness to `datetime('now','localtime')`, `STATUS = 'Pending'`, all other fields equal to the input (Eris, 100+ iterations).
2. **Migrated-schema field round trip**: same field and `STATUS` checks on the migrated schema.
3. **Archive copy**: approve, archive, and assert the archive row has the same non-empty date and fields, on both schemas.
4. **Backfilled rows**: rows present before migration keep their backfilled date after a later insert.

### Unit Tests

- Migrated-schema insert yields a formatted, non-empty date.
- `PRAGMA table_info` shows the migrated default is `''` (documents why the explicit value is needed).
- Archive of a migrated-schema row copies the date.
- Existing `WebRootLayoutTest` still passes (no engine SQL outside `src/db.php`).

### Property-Based Tests

- Fix-check property on the migrated schema (Property 1), 100+ iterations.
- Preservation property on the fresh schema (Property 2), 100+ iterations.
- Archive round trip property preserving the date on both schemas.

### Integration Tests

- Full flow on a migrated in-memory database: insert, list pending, approve, archive, read archive.
- Run the complete existing suite and confirm the existing 40 tests still pass, with `data/Db3.db` unmodified.

## Decision: Rows Already Saved With an Empty SUBMITTED_DATE

**Status: decided by the user.** No data repair. Rows saved with `SUBMITTED_DATE = ''` in `EMPDAYOFFREQ` or `EMPDAYOFFREQ_ARCHIVE` stay as they are, because their true submit time is unknown. `public/manageDays.php` shows "Unknown" in the Submitted column when the value is `''` (requirements 2.5, 3.8, 3.9). Whitespace-only values are out of scope. Archiving copies an empty value unchanged.

### Display Fix Design

**Files**: `public/manageDays.php`, plus a new `src/format.php`.

**Helper**: `formatSubmittedDate(string $value): string` returns `'Unknown'` when `$value === ''`, otherwise returns `$value` unchanged. It is pure PHP with no SQL, so `DataAccessIsolationTest` and `WebRootLayoutTest` still pass.

**Escaping**: the helper does NOT escape. The page keeps its existing `htmlspecialchars()` call and wraps the helper inside it, so escaping happens once, at the output boundary, as for every other column:

```php
<td><?php echo htmlspecialchars(formatSubmittedDate($row['SUBMITTED_DATE'])); ?></td>
```

**Both tables**: apply the same change to the pending table (currently line 96) and the history table (currently line 137).

**Where the helper lives (trade-off)**:

| Option | Verdict | Reason |
|---|---|---|
| New `src/format.php` (chosen) | Chosen | Keeps presentation helpers apart from validation. Cost: `manageDays.php` and `tests/bootstrap.php` each need one extra `require_once`. |
| Add to `src/validation.php` | Reject | No extra require in `tests/bootstrap.php`, but the function is not validation, and `manageDays.php` would still need a new `require_once` for it. |
| Inline ternary in both tables | Reject | No new file, but the rule is duplicated in two places. |

`tests/bootstrap.php` must add `require_once __DIR__ . '/../src/format.php';` so unit tests can call the helper. `public/manageDays.php` must add `require_once __DIR__ . '/../src/format.php';` after its existing `db.php` require.

### Display Tests

- **Unit** (`tests/Unit/FormatSubmittedDateTest.php`): `''` returns `'Unknown'`; `'2025-01-15 09:30:12'` is returned unchanged; a value containing HTML-special characters (for example `<b>&"`) is returned unchanged, which documents that escaping belongs to the page, not the helper. A string that is only whitespace is returned unchanged (out of scope).
- **Property** (Eris, 100+ iterations): for any non-empty string the helper returns the input; for `''` it returns `'Unknown'`.
- **Preservation of stored data**: on a migrated in-memory database containing a row with `SUBMITTED_DATE = ''`, run list, status update (approve and deny) and a second insert, then assert the `''` row still has `''` (no UPDATE of empty rows). Archiving that row copies `''` unchanged into the archive table. The fix adds no `UPDATE ... WHERE SUBMITTED_DATE = ''` anywhere.
- **Layout**: existing `WebRootLayoutTest` and `DataAccessIsolationTest` pass, confirming `src/format.php` and `public/manageDays.php` contain no SQL or engine-specific code.