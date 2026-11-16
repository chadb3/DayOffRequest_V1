# Requirements Document

## Introduction

This feature implements a complete employee day-off request system for a business transitioning from paper-based requests. Employees submit day-off requests through a web form, and management reviews, approves, or denies those requests through a dedicated management page. The system runs on a local network, uses SQLite for storage, and operates on an honor system without authentication in this version.

## Glossary

- **Request_Form**: The web page (dayOffRequest.php) where employees submit day-off requests
- **Management_Page**: The web page (manageDays.php) where managers view and act on submitted requests
- **Request**: A day-off request record stored in the SQLite database containing employee name, phone, shifts, substitute, reason, and status
- **Employee**: A staff member who submits day-off requests
- **Manager**: A staff member who reviews and approves or denies day-off requests
- **Status**: The current state of a request, one of: Pending, Approved, or Denied
- **Archive_Database**: A separate SQLite database where completed (approved/denied) requests can be moved for record-keeping
- **EMPDAYOFFREQ**: The SQLite table storing active day-off requests
- **Application**: The vanilla PHP and SQLite3 web application consisting of public/index.php, public/dayOffRequest.php, public/manageDays.php, public/styles.css, src/db.php, src/validation.php, src/nav.php, and bin/migrate.php
- **Supported_PHP_Version**: PHP 8.5 or later within a release branch that currently receives security patches from the PHP project. PHP 8.5 is the minimum version; the version in use is confirmed against php.net supported versions when the work is implemented
- **Data_Access_Layer**: The code that opens the database connection and is the only code that names the database engine, currently src/db.php
- **Main_Branch**: The Git branch named `master`, which holds the repository's primary history
- **Legacy_Branch**: The Git branch named `legacy`, which preserves the Application as it exists before this work
- **Dev_Branch**: The Git branch named `kiro-dev`, on which this work is performed
- **CI_Workflow**: A GitHub Actions workflow file stored under the `.github/workflows` directory
- **Test_Suite**: The PHPUnit tests under the `tests/` directory, including unit tests and Eris property-based tests that use an in-memory SQLite database
- **Web_Root**: The `public/` directory at the repository root, which is the document root of the PHP built-in server and the only directory whose files can be requested by URL
- **Repository_Root**: The top-level directory of the Git repository, which contains `public/`, `src/`, `bin/`, `data/`, `tests/`, `vendor/`, `docs/`, `.kiro/`, `composer.json`, and `phpunit.xml`
- **Database_File**: The SQLite file `data/Db3.db` and its write-ahead log companions `data/Db3.db-wal` and `data/Db3.db-shm`

## Requirements

### Requirement 1: Submit Day-Off Request

**User Story:** As an Employee, I want to submit a day-off request with my details and reason, so that management can review and approve my time off.

#### Acceptance Criteria

1. WHEN an Employee submits the Request_Form with name, phone, shifts, substitute, and reason, THE Request_Form SHALL insert a new Request into the EMPDAYOFFREQ table with a Status of "Pending"
2. WHEN an Employee submits the Request_Form, THE Request_Form SHALL use parameterized queries to prevent SQL injection
3. WHEN an Employee submits the Request_Form with any required field left empty, THE Request_Form SHALL display a validation error and not insert the Request
4. WHEN a Request is successfully submitted, THE Request_Form SHALL display a confirmation message to the Employee
5. THE Request_Form SHALL provide input fields for: name, phone, shifts, substitute, and a dropdown for reason (Medical, Dental, Family Emergency, Personal, Other)

### Requirement 2: View Pending Requests

**User Story:** As a Manager, I want to view all pending day-off requests, so that I can review them and make approval decisions.

#### Acceptance Criteria

1. WHEN a Manager navigates to the Management_Page, THE Management_Page SHALL display all Requests with a Status of "Pending" in a tabular format
2. THE Management_Page SHALL display the following columns for each Request: employee name, phone, shifts, substitute, reason, and submission date
3. WHEN no pending Requests exist, THE Management_Page SHALL display a message indicating there are no pending requests

### Requirement 3: Approve or Deny Requests

**User Story:** As a Manager, I want to approve or deny day-off requests, so that employees know whether their time off is granted.

#### Acceptance Criteria

1. WHEN a Manager clicks the approve action on a Request, THE Management_Page SHALL update the Request Status to "Approved"
2. WHEN a Manager clicks the deny action on a Request, THE Management_Page SHALL update the Request Status to "Denied"
3. WHEN a Manager approves or denies a Request, THE Management_Page SHALL refresh the pending requests list to reflect the change

### Requirement 4: View Request History

**User Story:** As a Manager, I want to view previously approved and denied requests, so that I can reference past decisions.

#### Acceptance Criteria

1. WHEN a Manager selects the history view on the Management_Page, THE Management_Page SHALL display all Requests with a Status of "Approved" or "Denied"
2. THE Management_Page SHALL display the status of each historical Request alongside the request details

### Requirement 5: Archive Completed Requests

**User Story:** As a Manager, I want to move completed requests to an archive database, so that the active request list stays manageable and records are preserved.

#### Acceptance Criteria

1. WHEN a Manager clicks the archive action on an approved or denied Request, THE Management_Page SHALL move the Request from the EMPDAYOFFREQ table to the Archive_Database
2. WHEN a Request is archived, THE Management_Page SHALL remove the Request from the active requests view
3. IF the archive operation fails, THEN THE Management_Page SHALL display an error message and retain the Request in the EMPDAYOFFREQ table

### Requirement 6: Database Schema Update

**User Story:** As a developer, I want the database schema to support request status tracking and timestamps, so that the approval workflow functions correctly.

#### Acceptance Criteria

1. THE EMPDAYOFFREQ table SHALL include columns for: NUM (auto-increment primary key), NAME, PHONE, SHIFTS, SUB, REASON, STATUS (default "Pending"), and SUBMITTED_DATE (default current timestamp)
2. THE Archive_Database SHALL use the same schema as the EMPDAYOFFREQ table with an additional ARCHIVED_DATE column
3. WHEN a new Request is inserted without an explicit Status, THE database SHALL default the Status to "Pending"

### Requirement 7: Navigation and Page Layout

**User Story:** As a user, I want consistent navigation across all pages, so that I can easily move between the request form and management views.

#### Acceptance Criteria

1. THE system SHALL display a navigation sidebar on every page with links to: Home, Request Day Off, and View Days Off (Management_Page)
2. THE system SHALL maintain consistent header and footer styling across all pages

### Requirement 8: Supported and Security-Patched PHP Version

**User Story:** As a developer, I want the Application to run on a currently supported PHP version, so that known PHP security issues are not present in the runtime.

#### Acceptance Criteria

1. THE Application SHALL declare PHP 8.5 as the minimum Supported_PHP_Version in composer.json
2. WHEN any Application file is executed on the Supported_PHP_Version with error_reporting set to E_ALL, THE Application SHALL emit zero deprecation notices
3. WHEN any Application file is executed on the Supported_PHP_Version with error_reporting set to E_ALL, THE Application SHALL emit zero warnings
4. THE Application SHALL use only the sqlite3 PHP extension for database access
5. THE README SHALL document the minimum Supported_PHP_Version and the PHP extensions the Application requires
6. THE README SHALL document how to run the Application with `php -S localhost:8000 -t public`, including how to run that command when the php executable is not on the system PATH

### Requirement 9: Preserve Current State in a Legacy Branch

**User Story:** As a developer, I want the current state of the code preserved in a `legacy` branch, so that I can return to the pre-upgrade Application if needed.

#### Acceptance Criteria

1. THE repository SHALL contain a Legacy_Branch created from the Main_Branch
2. THE Legacy_Branch SHALL point to the same commit as the Main_Branch at the time the Legacy_Branch is created
3. WHILE this work is in progress, THE repository SHALL keep all new commits on the Dev_Branch
4. THE Legacy_Branch SHALL remain free of commits made for Requirements 8 through 11

### Requirement 10: Automated Test Suite

**User Story:** As a developer, I want automated PHPUnit tests, so that regressions are detected when the code changes.

#### Acceptance Criteria

1. THE Test_Suite SHALL be runnable with the command `./vendor/bin/phpunit` from the repository root
2. THE Test_Suite SHALL use an in-memory SQLite database so that Db3.db is neither read nor modified
3. THE Test_Suite SHALL contain one Eris property-based test for each correctness property defined in design.md, and each test SHALL run at least 100 iterations
4. THE Test_Suite SHALL contain unit tests for form validation, the Pending default status, the empty pending-requests state, and archive rollback on failure
5. FOR ALL valid request data, inserting a Request and then reading the Request SHALL return field values equal to the inserted values (round-trip property)
6. IF any test in the Test_Suite fails, THEN THE Test_Suite SHALL exit with a non-zero status code
7. THE Test_Suite SHALL declare PHPUnit and Eris as development dependencies in composer.json

### Requirement 11: Continuous Integration with GitHub Actions

**User Story:** As a developer, I want GitHub Actions to run the tests automatically, so that every change is verified on the supported PHP version.

#### Acceptance Criteria

1. THE repository SHALL contain at least one CI_Workflow under the `.github/workflows` directory
2. WHEN a commit is pushed to the Main_Branch, the Dev_Branch, or the Legacy_Branch, THE CI_Workflow SHALL run the Test_Suite
3. WHEN a pull request is opened or updated, THE CI_Workflow SHALL run the Test_Suite
4. THE CI_Workflow SHALL install PHP 8.5 or later with the sqlite3 extension before running the Test_Suite
5. THE CI_Workflow SHALL install dependencies with Composer before running the Test_Suite
6. IF the Test_Suite exits with a non-zero status code, THEN THE CI_Workflow SHALL report the run as failed
7. THE CI_Workflow SHALL run the Test_Suite with error_reporting set to E_ALL so that deprecation notices cause a failed run

### Requirement 12: Isolated Data Access Layer

**User Story:** As a developer, I want database access isolated in one place, so that a later move to a different database engine requires limited code changes.

#### Acceptance Criteria

1. THE Data_Access_Layer SHALL be the only code in the Application that constructs a database connection
2. THE Data_Access_Layer SHALL be the only code in the Application that contains engine-specific SQL, including PRAGMA statements and the datetime('now','localtime') function
3. WHEN a page needs database access, THE page SHALL obtain the connection through a function defined in the Data_Access_Layer
4. THE Data_Access_Layer SHALL accept the database location as a parameter so that the Test_Suite can supply an in-memory database

### Requirement 13: Web Root Separation

**User Story:** As a developer, I want only browser-facing files inside the Web_Root, so that the database, migration script, and project files cannot be downloaded or run through a URL.

#### Acceptance Criteria

1. THE Web_Root SHALL contain exactly these files and no others: index.php, dayOffRequest.php, manageDays.php, and styles.css
2. THE Application SHALL keep db.php, validation.php, and nav.php in the `src/` directory at the Repository_Root
3. THE Application SHALL keep migrate.php in the `bin/` directory at the Repository_Root
4. THE Application SHALL keep the Database_File in the `data/` directory at the Repository_Root
5. THE Repository_Root SHALL keep `tests/`, `vendor/`, `docs/`, `.kiro/`, composer.json, composer.lock, and phpunit.xml outside the Web_Root
6. THE README SHALL document starting the Application with the command `php -S localhost:8000 -t public` run from the Repository_Root
7. WHILE the Application is served with `php -S localhost:8000 -t public`, WHEN a client requests /Db3.db, /data/Db3.db, /db.php, /src/db.php, /migrate.php, /bin/migrate.php, or /composer.json, THE PHP built-in server SHALL return a response body that contains none of the contents of the requested file and SHALL NOT execute the requested script
8. THE pages in the Web_Root SHALL include files from `src/` using paths built from the `__DIR__` constant
9. THE files in `src/` SHALL include other files using paths built from the `__DIR__` constant
10. WHEN the Application is started from any current working directory with the Web_Root as the document root, THE pages in the Web_Root SHALL load without include errors
11. THE Data_Access_Layer SHALL define DEFAULT_DB_PATH as the absolute path of `data/Db3.db`, resolved from the `__DIR__` constant of `src/db.php`
12. WHEN bin/migrate.php is executed and PHP_SAPI is not 'cli', THE migration script SHALL terminate without opening the database and without modifying the Database_File
13. WHEN bin/migrate.php is executed from the command line, THE migration script SHALL apply the schema migration to `data/Db3.db`
14. WHEN `./vendor/bin/phpunit` is run from the Repository_Root after the restructuring, THE Test_Suite SHALL pass using the new file paths
15. WHEN the CI_Workflow runs after the restructuring, THE CI_Workflow SHALL pass using the new file paths
16. WHEN the Test_Suite runs the data-access isolation test after the restructuring, THE data-access isolation test SHALL scan `src/` and `public/` and pass, which confirms that only the Data_Access_Layer constructs database connections and contains engine-specific SQL
17. THE README SHALL document the directory layout of `public/`, `src/`, `bin/`, `data/`, and `tests/`
18. THE navigation links in nav.php SHALL resolve to the pages in the Web_Root when the Application is served with `php -S localhost:8000 -t public`

## Out of Scope and Decisions

### Out of Scope

The following items are not requirements of this version.

- **LAMP migration**: Migrating the Application to a LAMP stack (Linux, Apache, MySQL or MariaDB, PHP) is out of scope. Requirement 12 keeps that migration feasible.
- **MySQL or MariaDB migration**: Moving from SQLite to MySQL or MariaDB, and transferring existing Db3.db data, is deferred to a later spec.
- **Apache deployment**: Serving the Application with Apache on Linux, and any network deployment configuration, is deferred to a later spec.
- **Authentication**: The honor-system model with no login is unchanged.

### Decisions

- **Database engine**: The Application stays on SQLite (Db3.db, sqlite3 PHP extension).
- **Web server**: The Application is served with the PHP built-in server using `php -S localhost:8000 -t public`, so that only the Web_Root is reachable by URL (Requirement 13).
- **Web root separation**: Only browser-facing files live in `public/`. Serving from the repository root exposed Db3.db (employee names, phones, and reasons), allowed migrate.php to run from a browser, and made composer.json, docs/, .kiro/, vendor/, and tests/ readable by URL.
- **PHP patch level**: The development machine runs PHP 8.5.12 (NTS, x64, Windows), which satisfies the 8.5 minimum. CI uses the latest PHP 8.5 patch release per Requirement 11. The minimum was raised from PHP 8.3 to PHP 8.5 for security reasons following a recent PHP security issue; PHP 8.5 is the newest supported branch (active support until 31 Dec 2027, security support until 31 Dec 2029 per php.net).
