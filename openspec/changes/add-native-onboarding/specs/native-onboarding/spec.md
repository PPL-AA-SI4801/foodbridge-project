# Spec Delta

## Purpose

Ensures a beginner contributor — typically on Laragon or XAMPP with no prior setup experience — can go from a fresh clone to a running FoodBridge app using only the repository's setup documentation (`docs/FIRST_SETUP.md`), without needing external documentation or maintainer help. The README stays slim and only covers starting an already-set-up app.

## ADDED Requirements

### Requirement: Exact toolchain versions are documented
The setup guide (`docs/FIRST_SETUP.md`) SHALL state the exact required versions of PHP (matching `composer.json`'s `^8.3` constraint), Composer 2.10 or newer (latest 2.x), Node.js 24 LTS or newer (also enforced via `package.json` `engines`), and MySQL 8 or MariaDB 10.x, including a brief rationale that these must match to avoid "works on my machine" bugs. PHP is deliberately the loosest constraint because Laragon/XAMPP users switch PHP versions freely; any PHP 8.3–8.5 is acceptable.

#### Scenario: Contributor checks versions before installing
- **WHEN** a contributor reads the setup guide's prerequisites section before setting up
- **THEN** they find each required tool with its exact version and where to verify it (e.g. `php -v`, `composer -V`, `node -v`)

### Requirement: Laragon setup path is documented
The setup guide SHALL provide step-by-step Laragon instructions covering: selecting the required PHP version, enabling the required PHP extensions for this project, and confirming MySQL is running.

#### Scenario: Laragon user enables required extensions
- **WHEN** a Laragon user's active PHP version does not match the required version and they follow the setup guide
- **THEN** they can select the correct version via the documented menu path and enable each required extension by editing `php.ini` and restarting Laragon

#### Scenario: Laragon user verifies extensions
- **THEN** the setup guide names every extension this project requires and states how to enable a disabled one (edit `php.ini`, restart Laragon)

### Requirement: XAMPP setup path is documented
The setup guide SHALL provide step-by-step XAMPP instructions covering: installing/switching to the required PHP version, uncommenting required extensions in `php.ini`, and starting MySQL from the XAMPP Control Panel.

#### Scenario: XAMPP user enables required extensions
- **WHEN** an XAMPP user follows the setup guide extension section
- **THEN** each documented `php.ini` line exists in a stock XAMPP install and the instructions state that Apache must be restarted after editing

### Requirement: Database creation via phpMyAdmin is documented
The setup guide SHALL instruct contributors to create the application database through phpMyAdmin (at `http://localhost/phpmyadmin`) with the database name and collation the project expects, before running migrations.

#### Scenario: Contributor creates the database
- **WHEN** a contributor follows the setup guide before ever running the app
- **THEN** they create the database through phpMyAdmin's UI (a step familiar from typical Laragon/XAMPP usage) with the exact name and collation the app expects, and it must be created before migrations run

### Requirement: First-run command sequence is documented
The setup guide SHALL list the full ordered first-run commands (install dependencies, create `.env`, generate app key, run migrations, install and build frontend assets, start the dev server) with the expected result (the app URL) stated at the end.

#### Scenario: Contributor runs first commands
- **WHEN** a contributor copies the documented commands into their terminal
- **THEN** the app reaches a running state at the documented URL without manual intervention

#### Scenario: Windows and Unix variants
- **WHEN** a command differs between Windows and Unix shells (e.g. `copy` vs `cp`)
- **THEN** the setup guide shows both variants rather than only one

### Requirement: Troubleshooting section addresses known failure modes
The setup guide SHALL include a troubleshooting section addressing at minimum: `php`/`npm`/`composer` not recognized (not on PATH), missing-extension errors, port 8000 already in use, and invalid/missing `APP_KEY`.

#### Scenario: Contributor self-serves a fix
- **WHEN** a contributor hits one of the documented failure modes
- **THEN** the setup guide's symptom description matches what they see and its fix resolves the issue without asking the maintainer

### Requirement: No external knowledge required
The setup guide SHALL contain no step that requires information from outside the repository (no "ask the maintainer", no unspecified prerequisite).

#### Scenario: Complete cold start
- **WHEN** a new contributor with only Laragon or XAMPP (plus Composer and Node) follows the setup guide top to bottom
- **THEN** they reach a running app without leaving the repository's documentation
