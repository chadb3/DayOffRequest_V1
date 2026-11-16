# Implementation Plan: Day-Off Request System

## Overview

This plan implements a complete employee day-off request system with vanilla PHP and SQLite3. The implementation progresses from shared infrastructure (database helper, navigation, migration) through the request form rewrite, to the full management page, and finally integration testing. Each task builds incrementally on the previous ones.

## Tasks

- [x] 1. Set up shared infrastructure files
  - [x] 1.1 Create `db.php` database connection helper
    - Implement `getDb()` function returning a configured SQLite3 instance
    - Enable WAL journal mode and set busy timeout
    - Use the existing `Db3.db` file path
    - _Requirements: 6.1, 1.2_

  - [x] 1.2 Create `nav.php` shared navigation include
    - Extract the sidebar navigation HTML into a reusable PHP include
    - Include links to Home (index.php), Request Day Off (dayOffRequest.php), and View Days Off (manageDays.php)
    - _Requirements: 7.1, 7.2_

  - [x] 1.3 Create `migrate.php` database migration script
    - Add STATUS column (TEXT, NOT NULL, DEFAULT 'Pending') to EMPDAYOFFREQ table
    - Add SUBMITTED_DATE column (TEXT, NOT NULL, DEFAULT datetime('now','localtime')) to EMPDAYOFFREQ table
    - Create EMPDAYOFFREQ_ARCHIVE table with matching schema plus ARCHIVED_DATE column
    - Handle the case where columns/table already exist gracefully
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 1.4 Update `index.php` to use shared includes
    - Replace inline navigation with `<?php include 'nav.php'; ?>`
    - Replace inline database connection with `require_once 'db.php'`
    - Clean up unused variables and commented-out code
    - _Requirements: 7.1, 7.2_

- [x] 2. Checkpoint - Verify infrastructure
  - Run migrate.php to update the database schema
  - Verify nav.php renders correctly when included
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Implement the employee request form (`dayOffRequest.php`)
  - [x] 3.1 Rewrite `dayOffRequest.php` with secure form handling
    - Include `db.php` and `nav.php`
    - Render form with fields: name (text), phone (text), shifts (text), substitute (text), reason (dropdown with Medical, Dental, Family Emergency, Personal, Other)
    - On POST: validate all required fields are non-empty and not whitespace-only
    - On POST: validate reason is from the allowed set
    - On validation failure: display error messages and preserve submitted field values in the form
    - On validation success: insert using parameterized query (prepared statement with bound parameters)
    - On successful insert: display confirmation message
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 3.2 Write property test: Valid submission defaults to Pending
    - **Property 1: Valid submission defaults to Pending**
    - Generate random valid form data (non-empty strings for all fields, reason from allowed set)
    - Insert via parameterized query without explicit status
    - Assert STATUS = "Pending" and all fields match submitted data
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 1.1, 6.3**

  - [x] 3.3 Write property test: SQL injection inputs stored literally
    - **Property 2: SQL injection inputs stored literally**
    - Generate strings containing SQL metacharacters (quotes, semicolons, DROP/DELETE, comment markers)
    - Submit as field values via parameterized query
    - Assert stored values equal original input strings verbatim
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 1.2**

  - [x] 3.4 Write property test: Empty field validation rejects submission
    - **Property 3: Empty field validation rejects submission**
    - Generate form data where at least one required field is empty or whitespace-only
    - Run validation function
    - Assert submission is rejected and database record count is unchanged
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 1.3**

- [x] 4. Checkpoint - Verify request form
  - Submit test requests through the form and verify they appear in the database with Status="Pending"
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement the management page (`manageDays.php`)
  - [x] 5.1 Implement pending requests view
    - Include `db.php` and `nav.php`
    - Query EMPDAYOFFREQ for records with STATUS = "Pending"
    - Display results in an HTML table with columns: Name, Phone, Shifts, Substitute, Reason, Submitted Date
    - Add Approve and Deny buttons for each row (POST forms with hidden `id` and `action` fields)
    - Display "No pending requests" message when result set is empty
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 5.2 Implement approve/deny actions
    - Handle POST with action "approve": update STATUS to "Approved" using parameterized query
    - Handle POST with action "deny": update STATUS to "Denied" using parameterized query
    - After action, redirect back to the pending view (POST-Redirect-GET pattern)
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 5.3 Implement history view
    - Add a toggle/link to switch between "Pending" and "History" views using GET parameter `view`
    - Query EMPDAYOFFREQ for records with STATUS IN ("Approved", "Denied")
    - Display results in a table with columns: Name, Phone, Shifts, Substitute, Reason, Status, Submitted Date
    - Add Archive button for each row in history view
    - _Requirements: 4.1, 4.2_

  - [x] 5.4 Implement archive action with transaction safety
    - Handle POST with action "archive": wrap in BEGIN/COMMIT transaction
    - Insert the record into EMPDAYOFFREQ_ARCHIVE (preserving all fields, adding ARCHIVED_DATE)
    - Delete the record from EMPDAYOFFREQ
    - On failure: ROLLBACK, display error message, retain record in active table
    - On success: redirect back to history view
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 5.5 Write property test: Status filtering returns correct subset
    - **Property 4: Status filtering returns correct subset**
    - Generate a set of requests with random statuses (Pending, Approved, Denied)
    - Query for pending, query for history
    - Assert pending returns only STATUS="Pending", history returns only STATUS IN ("Approved","Denied")
    - Assert union of both equals full record set
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 2.1, 4.1**

  - [x] 5.6 Write property test: Status update preserves all other fields
    - **Property 5: Status update preserves all other fields**
    - Generate random pending requests, update status to Approved or Denied
    - Assert only STATUS field changed; NAME, PHONE, SHIFTS, SUB, REASON, SUBMITTED_DATE remain identical
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 3.1, 3.2**

  - [x] 5.7 Write property test: Archive round-trip preserves data
    - **Property 6: Archive round-trip preserves data**
    - Generate approved/denied requests, perform archive operation (insert to archive + delete from active)
    - Assert archived record has identical NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE
    - Assert record no longer exists in active table
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 5.1**

- [x] 6. Checkpoint - Verify management page
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Styling and final integration
  - [x] 7.1 Update `styles.css` for new UI elements
    - Add styles for request tables (borders, padding, alternating row colors)
    - Add styles for action buttons (approve=green, deny=red, archive=gray)
    - Add styles for success/error messages
    - Add styles for form validation error display
    - Add styles for view toggle (pending/history)
    - _Requirements: 7.2_

  - [x] 7.2 Set up PHPUnit and Eris test configuration
    - Create `composer.json` with phpunit/phpunit and giorgiosironi/eris as dev dependencies
    - Create `phpunit.xml` configuration file pointing to `tests/` directory
    - Create `tests/` directory structure
    - _Requirements: (testing infrastructure)_

- [x] 8. Final checkpoint - Ensure all tests pass
  - Run full test suite with `./vendor/bin/phpunit tests/`
  - Verify all pages render correctly with shared navigation
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Isolate data access in `db.php`
  - [x] 9.1 Make the `db.php` connection function accept the database path as a parameter
    - Change `getDb()` to take a DB path argument (default to `Db3.db` for pages); keep WAL and busy timeout setup inside `db.php`
    - Update all callers and tests to pass the path explicitly where needed
    - _Requirements: 12.1, 8.4_

  - [x] 9.2 Move engine-specific SQL out of pages and `migrate.php` into `db.php`
    - Move `PRAGMA`, `datetime('now','localtime')`, and `SQLite3` class usage into `db.php` functions (e.g. a schema/migration function called by `migrate.php`)
    - Audit `dayOffRequest.php`, `manageDays.php`, `index.php`, `nav.php`, and `migrate.php` so each obtains its connection and runs all queries only through `db.php` functions (`insertRequest`, `getRequestById`, `countRequests`, `getPendingRequests`, `getHistoryRequests`, `updateRequestStatus`, `archiveRequest`, `getArchivedRequestById`)
    - _Requirements: 12.1, 12.2, 8.4_

  - [x] 9.3 Wire `dayOffRequest.php` to `validateRequest()` from `validation.php`
    - `require_once 'validation.php'` and replace inline validation with `validateRequest()`
    - Keep error display and field-value preservation behavior unchanged
    - _Requirements: 1.3, 1.4, 12.1_

  - [x] 9.4 Fix undefined-index warning in `manageDays.php`
    - Replace direct `$_SERVER['REQUEST_METHOD']` access with `$_SERVER['REQUEST_METHOD'] ?? ''`
    - Check other superglobal reads (`$_GET`, `$_POST`) in the page for the same issue
    - _Requirements: 8.2, 8.3_

  - [x] 9.5 Write unit test for data access isolation
    - Add a test that scans application `.php` files other than `db.php` (exclude `vendor/` and `tests/`)
    - Assert none contain `SQLite3`, `PRAGMA`, or `datetime(`
    - _Requirements: 12.1, 12.2_

- [x] 10. Complete unit and property test coverage
  - [x] 10.1 Write form validation unit tests
    - Cover valid input, each empty field, whitespace-only fields, and a reason outside the allowed set using `validateRequest()`
    - _Requirements: 10.4_

  - [x] 10.2 Write unit tests for default status and empty pending state
    - Assert a request inserted without explicit status has STATUS "Pending"
    - Assert `getPendingRequests()` returns an empty list on an empty database
    - _Requirements: 10.4, 10.6_

  - [x] 10.3 Write unit test for archive rollback on failure
    - Force a failure during `archiveRequest()` (e.g. a conflicting row in the archive table)
    - Assert the record remains in the active table and the archive table is unchanged
    - _Requirements: 10.4, 10.6_

  - [x] 10.4 Write property test: insert-then-read round trip
    - **Property 7: Insert-then-read round trip**
    - For any valid request, `insertRequest()` then `getRequestById()` returns equal NAME, PHONE, SHIFTS, SUB, and REASON values
    - Use PHPUnit with Eris, in-memory SQLite database
    - **Validates: Requirements 10.5**

  - [x] 10.5 Verify property test iteration counts and design coverage
    - Confirm each test in `tests/Property/` runs at least 100 iterations
    - Confirm exactly one property test exists per property in `design.md`, tagged `Feature: day-off-request, Property N`
    - _Requirements: 10.5_

- [x] 11. Update README for setup and running
  - [x] 11.1 Document prerequisites and setup in `README.MD`
    - State PHP 8.5+ minimum and required extensions (`sqlite3`, `mbstring`)
    - Note SQLite is bundled with PHP, so no separate SQL engine install is needed
    - Document `composer install`
    - _Requirements: 8.5, 8.6_

  - [x] 11.2 Document running the app and tests in `README.MD`
    - Explain how to run when `php` is not on PATH: use the full path to `php.exe`, or add it to PATH for the current session
    - Document the built-in server command (`php -S localhost:8000`) and `./vendor/bin/phpunit`
    - _Requirements: 8.5, 8.6_

- [x] 12. Add CI workflow
  - [x] 12.1 Create `.github/workflows/tests.yml`
    - Trigger on push to `master`, `kiro-dev`, `legacy` and on `pull_request`
    - Use `shivammathur/setup-php` with PHP 8.5 and extensions `sqlite3`, `mbstring`
    - Run `composer install`, then `php -d error_reporting=-1 vendor/bin/phpunit` so any non-zero exit fails the run
    - Note: Req 9 (legacy branch) is already satisfied and the user pushes branches manually, so no further task is needed
    - _Requirements: 11.1, 11.2, 11.3_

- [x] 13. Raise the minimum PHP version to 8.5
  - [x] 13.1 Update `composer.json` and `composer.lock`
    - Change `"php": ">=8.3"` to `">=8.5"` in `composer.json`
    - Refresh the content hash in `composer.lock` with `composer update --lock` (no dependency upgrades)
    - _Requirements: 8.1_

  - [x] 13.2 Update `README.MD` prerequisites
    - Change Prerequisites from "PHP 8.3 or newer" to "PHP 8.5 or newer"
    - Note that the minimum was raised for security
    - _Requirements: 8.5_

- [x] 14. Restructure into a public web root
  - [x] 14.1 Move files into the new layout with `git mv`
    - `public/`: `index.php`, `dayOffRequest.php`, `manageDays.php`, `styles.css`
    - `src/`: `db.php`, `validation.php`, `nav.php`
    - `bin/`: `migrate.php`
    - `data/`: `Db3.db`
    - Update `.gitignore` to add `data/*.db-wal` and `data/*.db-shm`
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

  - [x] 14.2 Fix include paths, database path, and migration guard
    - Public pages include `src/` files with `__DIR__ . '/../src/...'`
    - Files in `src/` include other files using `__DIR__`
    - Set `DEFAULT_DB_PATH = dirname(__DIR__) . '/data/Db3.db'` in `src/db.php`
    - Make `nav.php` links resolve under `public/`
    - `bin/migrate.php` requires `__DIR__ . '/../src/db.php'`; when `PHP_SAPI !== 'cli'` it sends HTTP 403, prints a message, and exits non-zero before opening the database
    - _Requirements: 13.8, 13.9, 13.10, 13.11, 13.12, 13.13_

  - [x] 14.3 Update and add tests for the new layout
    - `tests/bootstrap.php` requires files from `../src`
    - `DataAccessIsolationTest` scans `*.php` in `public/`, `src/`, and `bin/`, excluding `src/db.php`
    - Add a layout test asserting `public/` contains exactly `index.php`, `dayOffRequest.php`, `manageDays.php`, and `styles.css`, and that `Db3.db`, `db.php`, `migrate.php`, and `composer.json` are not in `public/`
    - _Requirements: 13.1, 13.5, 13.14_

  - [x] 14.4 Update README and verify the CI workflow
    - Document the layout, `php -S localhost:8000 -t public` (run from the repository root), and `php bin/migrate.php`
    - Verify `.github/workflows/tests.yml` needs no change because it runs from the repository root; change it only if a path is stale
    - _Requirements: 13.6, 13.13, 13.15_

  - [x] 14.5 Smoke-check the built-in server
    - Start `php -S localhost:8000 -t public` as a background process; send GET requests only and do not submit forms or POST, so `data/Db3.db` is not modified
    - Confirm `/index.php`, `/dayOffRequest.php`, `/manageDays.php`, and `/styles.css` return 200
    - Confirm `/Db3.db`, `/data/Db3.db`, `/db.php`, `/src/db.php`, `/migrate.php`, `/bin/migrate.php`, and `/composer.json` return none of the requested file's contents and do not run the script (`php -S` serves the home page for these)
    - Stop the server
    - _Requirements: 13.7, 13.10_

- [x] 15. Final checkpoint - Verify compliance
  - Run the full suite with `./vendor/bin/phpunit`
  - Run each page under `E_ALL` and confirm zero warnings or deprecations
  - Confirm `data/Db3.db` is unmodified by the test suite (compare hash before and after)
  - Confirm `composer.json` declares `php` `>=8.5`
  - Confirm `composer validate` reports the lock file is up to date
  - Confirm the moves show as renames in `git status`
  - Ensure all tests pass, ask the user if questions arise.
  - _Requirements: 8.1, 8.2, 8.3, 10.2, 13.14, 13.15_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties using PHPUnit + Eris
- All database operations use parameterized queries (prepared statements) to prevent SQL injection
- Tests use in-memory SQLite (`:memory:`) to avoid affecting the production Db3.db file

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["9.1", "11.1", "12.1"] },
    { "id": 1, "tasks": ["9.2", "9.4", "11.2"] },
    { "id": 2, "tasks": ["9.3"] },
    { "id": 3, "tasks": ["9.5", "10.1", "10.2", "10.3", "10.4"] },
    { "id": 4, "tasks": ["10.5"] },
    { "id": 5, "tasks": ["13.1", "13.2"] },
    { "id": 6, "tasks": ["14.1"] },
    { "id": 7, "tasks": ["14.2"] },
    { "id": 8, "tasks": ["14.3", "14.4"] },
    { "id": 9, "tasks": ["14.5"] }
  ]
}
```
