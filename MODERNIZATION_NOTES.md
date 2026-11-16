# Modernization Notes — pick up in the modernization spec

This file holds items that should be folded into the upcoming modernization
spec/task set (the one being generated in the IDE). They were added outside
that conversation, so they are recorded here so they are not lost.

## 1. Switch security-automation target branch to `main` at merge time
Currently the active branch is **`Kiro-Modernization`**, and the security
automation targets it directly:
- `.github/dependabot.yml` → `target-branch: "Kiro-Modernization"` (both ecosystems)
- `.github/workflows/security-audit.yml` → `push.branches: [Kiro-Modernization]`

**TASK:** When modernization merges and the default branch becomes `main`:
- Change `target-branch` in `dependabot.yml` to `main`.
- Update the `push.branches` list in `security-audit.yml` to `main`.
- Also consider adding the new default branch to `.github/workflows/tests.yml`
  (its triggers are currently `[master, kiro-dev, legacy]`).

## 2. Security automation additions (added now, outside the spec convo)
These were created directly on `Kiro-Modernization` and should be acknowledged
by the modernization spec so they are maintained, not duplicated:
- `.github/dependabot.yml` — weekly Composer + github-actions updates.
- `.github/workflows/security-audit.yml` — scheduled/PR/push `composer audit`;
  opens a GitHub issue on scheduled/push findings, fails the check on PRs.
- `.gitignore` — `composer.lock` is now **tracked** (was previously ignored),
  for reproducible installs and reliable auditing.

## 3. Deferred: add `Kiro-Modernization` to `tests.yml` triggers
`.github/workflows/tests.yml` currently triggers on `[master, kiro-dev, legacy]`,
so the PHPUnit suite does **not** run on `Kiro-Modernization` pushes.

**Decision:** intentionally left unchanged here — to be handled as part of the
IDE modernization work (avoid duplicating it). When addressed, add the active
modernization branch (and later `main`) to the `push.branches` list in
`tests.yml`.

### Known gap (future consideration)
`composer audit` covers **Composer dependencies only**. It does **not** detect
CVEs in the **PHP runtime itself** (e.g. a PHP 8.5.x interpreter advisory).
Since the app runs PHP directly (no container yet), there is no automated
runtime-CVE scan. If the LAMP modernization containerizes the app, add image
scanning (e.g. Trivy) using the same "open an issue on findings" pattern to
cover PHP/Apache/MySQL runtime CVEs.


## 4. Staged modernization plan (LAMP -> containerize)

Captured from discussion; to be folded into the IDE modernization spec.
The staging approach is deliberate: each stage is independently valuable and
testable, and we containerize a known-good app rather than debugging app logic
and container config at once.

### Stage 1 — LAMP
- [ ] 1a. Serve via Apache + PHP-FPM (replace the `php -S` built-in dev server)
- [ ] 1b. Migrate database SQLite -> MySQL/MariaDB  **(the real work of this stage)**
      - Current app uses SQLite (`ext-sqlite3`, `data/Db3.db`); moving to MySQL
        means SQL dialect changes, a connection/credentials layer, migrations,
        and rework in `src/db.php`.
      - Option: keep SQLite while adding Apache + PHP-FPM first, then do the
        MySQL migration as its own discrete step to keep changes small.

### Stage 2 — Containerize
- [ ] 2a. Dockerfile (`FROM php:8.5-fpm`) + compose (app + db services)
- [ ] 2b. Add image scanning (Trivy/Grype) — closes the PHP/Apache/MySQL
      runtime-CVE gap that `composer audit` cannot cover (see sections 2 & 5).

### Why this order
- LAMP first gives a properly-served, working app before adding container
  complexity.
- Containerizing an already-working app is far easier than doing both at once.
- Biggest change in Stage 1 is the DB ("M"), not the web server — treat it as
  its own sub-stage.

## 5. PHP runtime upgrades: make the next PHP CVE a non-event

Context: a PHP runtime CVE is infrastructure-level work, not an app-package
update. Real-world experience shows a PHP version bump is a *compatibility
event*, not a drop-in swap — "just run it on the new version" commonly fails
because deprecated APIs/behavior changes break code, and/or dependencies don't
yet support the new PHP. Expect to (a) rebuild/redeploy the artifact and
(b) fix code/deps the new runtime rejects.

Defenses to build in (in order of leverage):
- [ ] **Test against the next PHP version in CI early.** `tests.yml` already runs
      a PHP matrix (`php: ['8.5']`). Add the upcoming version (e.g.
      `['8.5', '8.6']`) ahead of time so CI flags incompatibilities *before*
      deployment — turns an emergency into a planned fix.
- [ ] **OS-level auto-patching + notification.** `unattended-upgrades` already
      auto-patches PHP on the Ubuntu VM (saw it bump to 8.5.4). Configure
      email/alerting so runtime patches are visible and just need a service
      restart confirmation.
- [ ] **Early-warning CVE feed (works without containers).** Scheduled job
      polling OSV/NVD for the `php` product that opens an issue on new entries —
      day-zero awareness to plan a maintenance window.
- [ ] **Containerize (Stage 2) for repeatable upgrades.** Moving PHP versions
      becomes "change base image tag, rebuild, redeploy" — testable in CI and
      rolled out identically everywhere, instead of hand-patching servers.
- [ ] **Staging + smoke tests** to validate a PHP bump quickly rather than
      manually re-testing by hand (where the "all day" usually goes).

Note: containerization makes the *rebuild/redeploy* trivial; a good test matrix
makes the *compatibility* safe. Both are needed — they solve different halves.
