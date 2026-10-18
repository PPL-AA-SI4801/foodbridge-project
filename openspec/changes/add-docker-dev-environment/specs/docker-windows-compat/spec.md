# Spec Delta

## Purpose

Guarantees the Docker dev environment works reliably for contributors on plain Windows (cmd/PowerShell, Docker Desktop with default WSL2 backend, no WSL knowledge) — covering line-ending integrity, file watching through bind mounts, and diagnosable Windows-specific failures.

## ADDED Requirements

### Requirement: Line-ending integrity
Repository `.gitattributes` SHALL force LF for shell scripts (`*.sh`) and CRLF for batch files (`*.bat`/`*.cmd`), so git's automatic conversion on Windows cannot produce scripts that fail to execute (e.g. `bad interpreter: No such file or directory`).

#### Scenario: Clone on Windows with default core.autocrlf
- **WHEN** a contributor with default Windows git settings clones the repository
- **THEN** `docker/entrypoint.sh` inside the working tree retains LF endings and executes correctly in the container, and `*.bat` wrappers run correctly in cmd

### Requirement: Vite file watching on Windows bind mounts
Vite's watcher SHALL support polling mode enabled via environment variable (set to on in the compose file), so file changes made on a Windows host are detected by the containerized Vite dev server. Hosts without polling (Linux/macOS) SHALL NOT incur polling overhead when the variable is unset or off.

#### Scenario: Windows edit triggers HMR
- **WHEN** polling is enabled via the environment and a contributor edits a watched frontend file on the Windows host
- **THEN** Vite detects the change and updates the browser within a few seconds

#### Scenario: No polling penalty by default outside compose
- **WHEN** Vite is run in an environment where the polling variable is unset or `false`
- **THEN** the watcher uses native file-system events (no polling)

### Requirement: Documented Windows onboarding and troubleshooting
The README Docker section SHALL include Windows-specific guidance: Docker Desktop per-user install (no admin, WSL2 enabled automatically — no WSL knowledge needed), cmd vs PowerShell wrapper syntax, and troubleshooting for the two known hard failures: virtualization disabled in BIOS (Docker cannot start) and port 8000 already in use.

#### Scenario: Beginner follows quickstart
- **WHEN** a Windows beginner with no prior Docker exposure follows only the README quickstart
- **THEN** they reach a running app at `http://localhost:8000` without needing external documentation

#### Scenario: Virtualization-blocked machine
- **WHEN** Docker Desktop fails to start because hardware virtualization is disabled
- **THEN** the README troubleshooting section identifies the symptom and the BIOS fix
