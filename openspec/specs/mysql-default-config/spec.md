# mysql-default-config Specification

## Purpose

Makes MySQL with stock Laragon/XAMPP credentials the project's default database configuration, so a fresh clone connects successfully with zero configuration changes for contributors using those tools.

## Requirements

### Requirement: Environment template defaults to Laragon/XAMPP MySQL
`.env.example` SHALL default to `DB_CONNECTION=mysql` with `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_USERNAME=root`, and an empty `DB_PASSWORD`, matching stock Laragon and XAMPP credentials. SQLite settings SHALL NOT remain the active (uncommented) default.

#### Scenario: Fresh clone with stock Laragon/XAMPP
- **WHEN** a contributor copies `.env.example` to `.env` with their local MySQL running and default credentials
- **THEN** the application connects to MySQL without editing any database variables

#### Scenario: SQLite no longer the default
- **WHEN** a contributor inspects `.env.example`
- **THEN** the active database block is MySQL, with SQLite options absent or clearly commented out as a non-default alternative

### Requirement: Application data lives in MySQL after setup
Following the documented setup (create database, migrate) SHALL provision all application tables — sessions, cache, and queue included — in MySQL per the project's existing `.env.example` service defaults (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`).

#### Scenario: Migrate on empty database
- **WHEN** `php artisan migrate` runs against a freshly created empty `foodbridge` database
- **THEN** all migrations complete successfully and the app serves requests using MySQL for session, cache, and queue

### Requirement: Project scripts assume MySQL
Composer scripts SHALL NOT create or depend on a SQLite database file, keeping scripted setup consistent with the MySQL default.

#### Scenario: Scripted setup on fresh clone
- **WHEN** the `composer setup` script runs on a fresh clone whose `.env` points at MySQL with an existing empty database
- **THEN** no step fails due to missing SQLite files, and the app is migrated and ready

#### Scenario: Existing local SQLite database is orphaned gracefully
- **WHEN** an existing contributor pulls this change while their `.env` still says `sqlite`
- **THEN** nothing breaks silently in the repository itself; their documented migration step is to update `.env` and re-run `php artisan migrate` against MySQL (called out in the `docs/FIRST_SETUP.md` upgrade note)
