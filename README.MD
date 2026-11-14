This project allows days off to be scheduled easily!

More of a prototype of a more advanced one to come down the line/ proof of concept/demo

Ment for a business that still uses paper day off requests.
Ment to be ran on a local network.
These are the features version one.(implimented or soon to be)
	
	-Uses the honor system (no log on for this version!)
	-employee has to put shifts in plane txt along with each replacement
	-employee has to pick a basic reason 
	-Employer page to see and manage requests(Not Yet Implimented)
	-Employer can move the request to a separate database as a record( NYI)
	-you may need to deal with permissions to get it to write to the DB

## Prerequisites

- **PHP 8.5 or newer** (the development machine uses PHP 8.5.12, NTS, x64). The minimum was raised from 8.3 to 8.5 for security reasons; 8.5 is the newest supported PHP branch.
- PHP extensions: `sqlite3` and `mbstring`. Enable them in `php.ini` (`extension=sqlite3`, `extension=mbstring`) if they are not already on.
- **SQLite is bundled with PHP** (through the `sqlite3` extension), so no separate SQL engine needs to be installed.
- [Composer](https://getcomposer.org/) for installing dev dependencies such as PHPUnit.

## Setup

Install dependencies from the repository root:

```
composer install
```

If `php` is not on your PATH, call it by its full path. For example, with a `composer.phar` in the PHP folder:

```
C:\php\php-8.5.12-1-nts-Win32-vs17-x64\php.exe C:\php\php-8.5.12-1-nts-Win32-vs17-x64\composer.phar install
```

Composer may need the `zip` extension to unpack packages. If it is not enabled in `php.ini`, enable it for a single run:

```
php -d extension=zip composer.phar install
```

## Running the App

If `php` is not recognized, either use the full path to `php.exe` (for example `C:\php\php-8.5.12-1-nts-Win32-vs17-x64\php.exe`) in place of `php` in the commands below, or add PHP to PATH for the current PowerShell session only:

```
$env:Path += ";C:\php\php-8.5.12-1-nts-Win32-vs17-x64"
```

If you already have a `Db3.db` from an earlier version, run the migration once to update its schema:

```
php migrate.php
```

Start the PHP built-in server from the repository root:

```
php -S localhost:8000
```

Then open <http://localhost:8000/> in a browser.

## Running the Tests

Run the PHPUnit suite from the repository root:

```
./vendor/bin/phpunit
```

On Windows, run it through PHP instead:

```
php vendor/bin/phpunit
```

## AI Assisted Development

This project is developed with assistance from Kiro, an AI-powered development environment. AI was used for code generation, architecture design, and documentation. All AI-generated code has been reviewed and approved by a human developer.

