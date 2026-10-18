# Spec Delta

## Purpose

Lets contributors run `artisan` and `composer` commands inside the container using nearly the same syntax as a vanilla Laravel install, from cmd, PowerShell, and bash — so Windows beginners never have to learn Docker's `exec` syntax.

## ADDED Requirements

### Requirement: Artisan wrapper
The project SHALL provide `artisan` (bash) and `artisan.bat` (Windows) wrapper scripts that proxy all arguments to `php artisan` inside the running container.

#### Scenario: Windows cmd usage
- **WHEN** a contributor runs `artisan migrate` in cmd from the project root
- **THEN** migrations execute inside the container and output is printed to the console

#### Scenario: PowerShell usage
- **WHEN** a contributor runs `.\artisan migrate` in PowerShell from the project root
- **THEN** migrations execute inside the container and output is printed to the console

#### Scenario: bash usage
- **WHEN** a contributor runs `./artisan migrate` in a bash shell from the project root
- **THEN** migrations execute inside the container and output is printed to the console

#### Scenario: Pass-through of arguments and exit codes
- **WHEN** a contributor runs a wrapper with arguments (e.g. `artisan tinker`, `composer require foo/bar`)
- **THEN** all arguments are forwarded verbatim and the wrapper exits with the container command's exit code

### Requirement: Composer wrapper
The project SHALL provide `composer` (bash) and `composer.bat` (Windows) wrapper scripts that proxy all arguments to Composer inside the running container, using the container's pinned Composer version.

#### Scenario: Package installation
- **WHEN** a contributor runs `composer require vendor/package` via a wrapper
- **THEN** the package is installed inside the container with the pinned Composer version and the change is reflected in `composer.json`/`composer.lock` on the host

### Requirement: Container-not-running feedback
When the container is not running, the wrappers SHALL print a clear, actionable message instead of a raw Docker error.

#### Scenario: Wrapper invoked while containers are down
- **WHEN** a contributor runs a wrapper while the app container is not running
- **THEN** the wrapper prints a message telling them to run `docker compose up` first and exits non-zero
