# Human Tasks — Day-Off Request System

These are the manual steps you need to do on your Linux VM to get things running.

## 1. Set Up Your Environment

- [ ] Install PHP (7.4+ recommended) with SQLite3 extension
  ```bash
  sudo apt update
  sudo apt install php php-sqlite3
  ```
- [ ] Verify PHP is working:
  ```bash
  php -v
  ```

## 2. Run the Database Migration

- [ ] From the project root, run:
  ```bash
  php migrate.php
  ```
  This adds the STATUS and SUBMITTED_DATE columns to your existing table and creates the archive table. Safe to run multiple times.

## 3. Start a Local Dev Server

- [ ] Run PHP's built-in server:
  ```bash
  php -S localhost:8080
  ```
- [ ] Open `http://localhost:8080` in your browser

## 4. Test the Workflow

- [ ] Navigate to "Request Day Off" and submit a test request
- [ ] Navigate to "View Days Off" and verify the request shows up as Pending
- [ ] Try approving/denying a request
- [ ] Try archiving a completed request
- [ ] Try submitting with empty fields to verify validation works

## 5. (Optional) Set Up Testing

- [ ] Install Composer: https://getcomposer.org/download/
- [ ] Install test dependencies:
  ```bash
  composer install
  ```
- [ ] Run tests:
  ```bash
  ./vendor/bin/phpunit tests/
  ```

## 6. Network Deployment (When Ready)

- [ ] Configure your Linux VM's firewall to allow traffic on your chosen port
- [ ] Run the server bound to all interfaces:
  ```bash
  php -S 0.0.0.0:8080
  ```
- [ ] Other machines on the LAN can access it at `http://<your-vm-ip>:8080`

## Notes

- The `Db3.db` file needs to be writable by the PHP process
- If you get permission errors: `chmod 664 Db3.db && chmod 775 .`
- For production use, consider running behind Apache or Nginx instead of the built-in server
