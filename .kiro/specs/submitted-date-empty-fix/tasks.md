# Implementation Plan

Environment notes: work on branch `kiro-dev`. PHP is at `C:\php\php-8.5.12-1-nts-Win32-vs17-x64\php.exe` and is not on PATH. Run tests from the repo root with `php vendor/bin/phpunit` (using the full php path). All tests use in-memory SQLite and never read or modify `data/Db3.db`.

- [x] 1. Write bug condition exploration test
  - **Property 1: Bug Condition** - Submitted date is set on every insert into a migrated database
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - First verify the legacy `EMPDAYOFFREQ` columns from git history before writing the helper. Read the old migrate script, for example `git log --all -p -- migrate.php bin/migrate.php` and `git show <commit>:migrate.php` (commits `309ecd9`, `14575bf`, `9d1cd97`, `60e2171`, `6cf6f62`), or the `legacy` branch code. `git show <master-commit>:Db3.db` is binary, so prefer reading the old script. Confirm or correct the inferred shape `EMPDAYOFFREQ(NUM INTEGER PRIMARY KEY AUTOINCREMENT, NAME VARCHAR(255) NOT NULL, PHONE BLOB, REASON VARCHAR(255))` and record the result in a comment in the helper
  - Add the test helper (for example `createMigratedDb()` in a new `tests/MigratedDbTestCase.php`, or a protected method on `DbTestCase`): open `getDb(':memory:')`, create the verified legacy table, optionally insert a legacy row to exercise the one-time backfill, then call `migrateSchema()`
  - **Scoped PBT Approach**: the bug is deterministic for every migrated database, so scope the property to the migrated in-memory schema with generated valid request fields (reuse the generators from `InsertReadRoundTripPropertyTest`, 100+ iterations)
  - Bug Condition from design: `isBugCondition(X)` where `X.database.EMPDAYOFFREQ.SUBMITTED_DATE.default = ''`
  - Test: `insertRequest()` on the migrated database, read the row with `getRequestById()`, assert `SUBMITTED_DATE` matches `/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/` and is within a few seconds of `SELECT datetime('now','localtime')` from the same connection (compare as plain strings, no timezone conversion)
  - Also add example tests: migrated insert then `archiveRequest()` gives a non-empty archive date, and `PRAGMA table_info(EMPDAYOFFREQ)` shows `dflt_value` of `''` for `SUBMITTED_DATE` (root cause check, this one passes on unfixed code)
  - Run on UNFIXED code: `php vendor/bin/phpunit`
  - **EXPECTED OUTCOME**: The insert and archive tests FAIL (this is correct - it proves the bug exists)
  - Document counterexamples found (for example "insertRequest() on a migrated table stores SUBMITTED_DATE = ''")
  - Mark task complete when the test is written, run, and the failure is documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.3, 2.4_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Existing insert, status, field, archive and backfill behavior
  - **IMPORTANT**: Follow observation-first methodology
  - Observe on UNFIXED code with non-buggy inputs (fresh `createSchema()` database): `SUBMITTED_DATE` is a formatted current local date-time, `STATUS` is `'Pending'`, and NAME, PHONE, REASON, SHIFTS, SUB equal the submitted values
  - Observe on UNFIXED code: archiving a row with a non-empty date copies it and all other fields unchanged; rows backfilled at migration keep their date after later inserts; a row with `SUBMITTED_DATE = ''` stays `''` through list, approve, deny and a second insert, and archiving it copies `''` unchanged
  - Write property-based tests (Eris, 100+ iterations, generators include quotes, unicode and SQL-like text):
    - fresh-schema insert: date format and closeness to `datetime('now','localtime')`, `STATUS = 'Pending'`, fields equal to input
    - migrated-schema field round trip: `STATUS` and the five fields equal the input (do not assert the date here, it is the buggy value)
    - archive copy on both schemas for rows with a non-empty date (insert a row with an explicit date on the migrated schema so the test passes on unfixed code)
    - backfilled legacy row keeps its backfilled date after a later insert
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.7, 3.8_

- [x] 3. Fix for empty SUBMITTED_DATE on migrated databases

  - [x] 3.1 Make insertRequest() set SUBMITTED_DATE explicitly
    - In `src/db.php`, change the INSERT in `insertRequest()` to `INSERT INTO EMPDAYOFFREQ (NAME, PHONE, SHIFTS, SUB, REASON, SUBMITTED_DATE) VALUES (:name, :phone, :shifts, :sub, :reason, datetime('now','localtime'))`
    - Leave STATUS out so the `'Pending'` default still applies on both schemas
    - Replace the docblock text "STATUS and SUBMITTED_DATE come from column defaults" with text saying STATUS comes from the column default and SUBMITTED_DATE is set explicitly because migrated tables have `DEFAULT ''`
    - Do not change `createSchema()`, `migrateSchema()`, `archiveRequest()` or add any `UPDATE ... WHERE SUBMITTED_DATE = ''`
    - _Bug_Condition: isBugCondition(X) where X.database.EMPDAYOFFREQ.SUBMITTED_DATE.default = ''_
    - _Expected_Behavior: row.SUBMITTED_DATE matches YYYY-MM-DD HH:MM:SS and equals current local date-time at insert_
    - _Preservation: fresh-schema inserts, STATUS default, parameterized field storage, archive/restore, existing row values, engine SQL only in src/db.php_
    - _Requirements: 2.1, 2.3, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.8_

  - [x] 3.2 Add formatSubmittedDate() helper and load it in bootstrap
    - Create `src/format.php` with `formatSubmittedDate(string $value): string` returning `'Unknown'` when `$value === ''`, otherwise `$value` unchanged
    - The helper is pure PHP: no SQL, no escaping (escaping stays at the output boundary)
    - Add `require_once __DIR__ . '/../src/format.php';` to `tests/bootstrap.php`
    - _Bug_Condition: stored SUBMITTED_DATE = '' (rows saved since the refactor)_
    - _Expected_Behavior: empty value displays as "Unknown"; non-empty value is returned unchanged_
    - _Preservation: no SQL or engine-specific code outside src/db.php_
    - _Requirements: 2.5, 3.6, 3.9_

  - [x] 3.3 Use the helper in public/manageDays.php
    - Add `require_once __DIR__ . '/../src/format.php';` after the existing `db.php` require
    - In both the pending table (about line 96) and the history table (about line 137), output `htmlspecialchars(formatSubmittedDate($row['SUBMITTED_DATE']))`
    - Do not add any file to `public/` (it must still contain exactly the four files)
    - _Expected_Behavior: "Unknown" shown for `''` in both views; non-empty dates shown as stored, HTML-escaped_
    - _Preservation: other columns and page behavior unchanged_
    - _Requirements: 2.2, 2.5, 3.9_

  - [x] 3.4 Write display tests
    - Unit test `tests/Unit/FormatSubmittedDateTest.php`: `''` gives `'Unknown'`; `'2025-01-15 09:30:12'` unchanged; a value with HTML-special characters (`<b>&"`) unchanged (escaping belongs to the page); a whitespace-only string unchanged (out of scope)
    - Property test (Eris, 100+ iterations): any non-empty string is returned as-is; `''` gives `'Unknown'`
    - Stored-data preservation: on a migrated in-memory database with a row where `SUBMITTED_DATE = ''`, run list, approve, deny and a second insert, assert the `''` row is still `''`; archiving it copies `''` unchanged
    - Integration: full flow on a migrated in-memory database (insert, list pending, approve, archive, read archive) with a real date at every step
    - _Requirements: 2.4, 2.5, 3.8, 3.9_

  - [x] 3.5 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Submitted date is set on every insert into a migrated database
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - Run the bug condition exploration test from task 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 3.6 Verify preservation tests still pass
    - **Property 2: Preservation** - Existing insert, status, field, archive and backfill behavior
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from task 2, plus `DataAccessIsolationTest` and `WebRootLayoutTest`
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Confirm all tests still pass after fix (no regressions)

- [x] 4. Checkpoint - Ensure all tests pass
  - Record the SHA-256 of `data/Db3.db` before running anything: `Get-FileHash data/Db3.db -Algorithm SHA256`
  - Run the full suite from the repo root: `php vendor/bin/phpunit` (the earlier 40 tests plus the new ones all pass, including `DataAccessIsolationTest` and `WebRootLayoutTest`)
  - Render `public/manageDays.php` under `error_reporting(E_ALL)` (for example `php -d error_reporting=E_ALL -d display_errors=1` against a temporary copy or in-memory setup, never the real `data/Db3.db`) and confirm no notices, warnings or errors, and that an empty date shows "Unknown" in both tables
  - Re-compute the SHA-256 of `data/Db3.db` and confirm it is identical to the value recorded before
  - Confirm `public/` contains exactly `dayOffRequest.php`, `index.php`, `manageDays.php`, `styles.css`, and `src/format.php` is the only new file in `src/`
  - Manual verification for the user on the Linux VM: submit a new day-off request and confirm the Submitted column on the management page shows a real date-time; confirm older rows saved with an empty date show "Unknown"
  - Ensure all tests pass, ask the user if questions arise.
  - _Requirements: 1.4, 2.4, 3.6, 3.7_

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1"] },
    { "id": 1, "tasks": ["2"] },
    { "id": 2, "tasks": ["3.1", "3.2"] },
    { "id": 3, "tasks": ["3.3", "3.4"] },
    { "id": 4, "tasks": ["3.5", "3.6"] },
    { "id": 5, "tasks": ["4"] }
  ]
}
```
