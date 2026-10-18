# Spec Delta

## Purpose

Ensures a beginner contributor — typically on Laragon or XAMPP with no prior setup experience — can go from a fresh clone to a running FoodBridge app using only the repository's README, without needing external documentation or maintainer help.

## ADDED Requirements

### Requirement: Exact toolchain versions are documented
The README SHALL state the exact required versions of PHP (matching `composer.json`'s `^8.3` constraint), Composer 2.10 or newer (latest 2.x), Node.js 24 LTS or newer (also enforced via `package.json` `engines`), and MySQL 8 or MariaDB 10.x, including a brief rationale that these must match to avoid "works on my machine" bugs. PHP is deliberately the loosest constraint because Laragon/XAMPP users switch PHP versions freely; any PHP 8.3–8.5 is acceptable.

#### Scenario: Contributor checks versions before installing
- **WHEN** a contributor reads the README prerequisites section before setting up
- **THEN** they find each required tool with its exact version and where to verify it (e.g. `php -v`, `composer -V`, `node -v`)

### Requirement: Laragon setup path
The README SHALL provide step-by-step Laragon instructions covering: selecting the required PHP version, enabling the required PHP extensions for this project, and confirming MySQL is running.

#### Scenario: Laragon user with wrong PHP version
- **WHEN** a Laragon user's active PHP version does not match the required version and they follow the README
- **THEN** they reach the correct version by following the documented menu path, and can verify success with `php -v`

#### Scenario: Laragon user with missing extensions
- **WHEN** a Laragon user follows the extension checklist
- **THEN** the README names every extension this project requires and states how to enable a disabled one (edit `php.ini`, restart Laragon)

### Requirement: XAMPP setup path
The README SHALL provide step-by-step XAMPP instructions covering: installing/switching to the required PHP version, uncommenting required extensions in `php.ini`, and starting MySQL from the XAMPP Control Panel.

#### Scenario: XAMPP user enables extensions
- **WHEN** an XAMPP user follows the README extension section
- **THEN** they are told exactly which `php.ini` lines to uncomment and that Apache must be restarted afterward, and the documented verification command confirms each extension is loaded

### Requirement: Database creation via phpMyAdmin
The README SHALL instruct contributors to create the application database through phpMyAdmin (at `http://localhost/phpmyadmin`) with the database name and collation the project expects, before running migrations.

#### Scenario: First-time database creation
- **WHEN** a contributor follows the README before ever running the app
- **THEN** they create the correctly named and collated database in phpMyAdmin and the subsequent `php artisan migrate` succeeds against it

### Requirement: Complete first-run command sequence
The README SHALL list the full ordered first-run commands (install dependencies, create `.env`, generate app key, run migrations, install and build frontend assets, start the dev server) with the expected result (the app URL) stated at the end.

#### Scenario: Contributor follows the sequence verbatim
- **WHEN** a contributor with a correct environment runs each documented first-run command in order
- **THEN** no step is missing or out of order, and the final result is a working app at the documented URL

#### Scenario: Windows and non-Windows commands
- **WHEN** a documented command differs between Windows (cmd) and macOS/Linux
- **THEN** the README shows both variants (e.g. `copy` vs `cp`) rather than only one

### Requirement: Troubleshooting covers the known failure modes
The README SHALL include a troubleshooting section addressing at minimum: `php`/`npm`/`composer` not recognized (not on PATH), missing-extension errors, port 8000 already in use, and invalid/missing `APP_KEY`.

#### Scenario: Beginner hits a known failure
- **WHEN** a contributor encounters one of the documented failure modes
- **THEN** the README's symptom description matches what they see and its fix resolves the issue without asking the maintainer

### Requirement: Onboarding is self-sufficient
The README SHALL contain no step that requires information from outside the repository (no "ask the maintainer", no unspecified prerequisite).

#### Scenario: Cold-start walkthrough
- **WHEN** a new contributor with only Laragon or XAMPP (plus Composer and Node) follows the README top to bottom
- **THEN** they reach a running application at the documented URL without consulting external sources
