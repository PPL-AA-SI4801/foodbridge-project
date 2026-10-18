# Spec Delta

## Purpose

Provides a containerized development environment for this Laravel project so contributors can start and develop the app with a single Docker command, without installing PHP, Composer, or Node locally.

## ADDED Requirements

### Requirement: One-command startup
The environment SHALL start the full application with `docker compose up`, requiring no other setup commands from a fresh clone, provided Docker Desktop is running.

#### Scenario: Fresh clone first start
- **WHEN** a contributor clones the repository on a machine with Docker running and executes `docker compose up` in the project root
- **THEN** the application becomes available at `http://localhost:8000` without any manual setup steps

#### Scenario: Subsequent starts
- **WHEN** a contributor who has already set up the project executes `docker compose up`
- **THEN** the application starts with existing configuration, dependencies, and data intact

### Requirement: First-run automatic provisioning
On first start, the environment SHALL automatically perform the equivalent of the project's `composer setup` script: create `.env` from `.env.example` if missing, install Composer dependencies, generate the application key, create and migrate the SQLite database, and install npm dependencies.

#### Scenario: No .env exists
- **WHEN** the containers start and `.env` does not exist
- **THEN** `.env` is created from `.env.example` and an `APP_KEY` is generated

#### Scenario: Empty vendor directory
- **WHEN** the containers start and `vendor/` is empty
- **THEN** `composer install` runs to completion before the application serves traffic

#### Scenario: No database file
- **WHEN** the containers start and `database/database.sqlite` does not exist
- **THEN** the file is created and migrations run successfully

#### Scenario: Provisioning steps are observable
- **WHEN** any provisioning step runs
- **THEN** each step logs a clearly labeled message, and a failing step halts startup with a visible, human-readable error rather than a silent exit

### Requirement: Pinned toolchain versions
The container image SHALL pin PHP 8.3 (matching `composer.json`'s `^8.3`), a specific Composer major version, and a specific Node LTS major version, so all contributors share identical toolchain versions regardless of their host machines.

#### Scenario: Version mismatch impossible
- **WHEN** a contributor runs PHP, Composer, or Node commands through the environment
- **THEN** the versions used are those baked into the image, independent of anything installed on the host

### Requirement: Development file synchronization
Edits made on the host SHALL be reflected inside the container immediately (bind mount), while `vendor/` and `node_modules/` SHALL live in container-managed volumes so host filesystem performance and file-count overhead do not affect them.

#### Scenario: Code edit visible in container
- **WHEN** a contributor edits a PHP file in the project directory on the host
- **THEN** the edited file is served by the application in the container without rebuilding the image

#### Scenario: Dependencies not written to host
- **WHEN** `composer install` or `npm install` runs in the container
- **THEN** `vendor/` and `node_modules/` are populated inside named volumes, not on the host filesystem

### Requirement: Data persistence across container lifecycle
Application data (the SQLite database and `storage/` contents) SHALL survive `docker compose down` and container rebuilds.

#### Scenario: Database survives restart
- **WHEN** a contributor runs `docker compose down` and then `docker compose up`
- **THEN** the SQLite database file and its data are still present and the app functions with prior data

### Requirement: Vite dev server with HMR
The environment SHALL run the Vite dev server on port 5173 bound to all interfaces, and the app at `http://localhost:8000` SHALL load assets through it in development.

#### Scenario: Assets served in dev
- **WHEN** the environment is running and a Blade view uses the Vite directive
- **THEN** CSS/JS assets are served from the Vite dev server and hot reload applies on change
