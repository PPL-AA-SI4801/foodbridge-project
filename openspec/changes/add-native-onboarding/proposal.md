# Proposal

## Why

Contributors (beginners, mostly on Laragon/XAMPP) must reverse-engineer which PHP/Composer/Node versions and MySQL setup this Laravel 13 project expects; version drift and missing extensions are the top onboarding failures. Since the team chose native tooling over containers, onboarding must instead be achieved through exact, written instructions matched to the tools they already have — plus environment defaults that fit Laragon/XAMPP out of the box.

## What Changes

- Switch the project's default database from SQLite to MySQL using stock Laragon/XAMPP credentials (`root`, empty password, `127.0.0.1:3306`), **BREAKING** existing local SQLite setups (fresh `migrate` required).
- Add a README onboarding section with: exact toolchain versions (PHP 8.3+, Composer 2.10+, Node.js 24 LTS+, MySQL 8/MariaDB 10.x), step-by-step Laragon and XAMPP setup incl. required PHP extensions, database creation via phpMyAdmin, the full first-run command sequence, and a troubleshooting section for the known failure modes.
- Declare the Node.js prerequisite in `package.json` `engines` (`>=24`) so npm warns contributors on outdated Node.
- Clean up SQLite leftovers in `composer.json` scripts (the `post-create-project-cmd` sqlite touch) so scripts match the MySQL default.
- Explicitly out of scope (recorded decisions): no Docker, no setup/doctor scripts, no Composer `platform` pin — the README is the single onboarding mechanism.

## Capabilities

### New Capabilities

- `native-onboarding`: README-driven setup guide covering toolchain versions, Laragon/XAMPP configuration, phpMyAdmin database creation, first-run commands, and troubleshooting — complete enough that a beginner reaches a running app without external help.
- `mysql-default-config`: The environment template defaults to MySQL with stock Laragon/XAMPP credentials, and project scripts assume MySQL rather than SQLite.

### Modified Capabilities

(none — no existing specs)

## Impact

- Modified: `README.md` (new onboarding section), `.env.example` (DB block → MySQL), `composer.json` (drop sqlite touch from `post-create-project-cmd`), `package.json` (add `engines.node >=24`).
- Existing local SQLite dev databases stop being used by default; contributors re-run `php artisan migrate` against MySQL.
- No application code, routes, or schema changes (Laravel migrations are DB-agnostic here).
- Accepted trade-off (decided with maintainer): no automation scripts — README quality is the sole onboarding guarantee.
