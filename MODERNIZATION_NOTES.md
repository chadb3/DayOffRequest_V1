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
