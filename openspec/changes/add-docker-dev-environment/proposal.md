# Proposal

## Why

Contributors must install and keep matching specific PHP (8.3+), Composer, and Node versions locally to run this Laravel 13 project; version drift is the top onboarding failure. We want a single-command environment (`docker compose up`) so friends — most of them beginners on plain Windows (cmd/PowerShell, no WSL knowledge) — can develop without installing any language toolchain.

## What Changes

- Add a single dev container image pinning PHP 8.3-cli, Composer, and Node 22, with required PHP extensions (`pdo_sqlite`, `mbstring`, `zip`, etc.).
- Add a `docker-compose.yml` with one `app` service: repo bind-mounted, named volumes isolating `vendor/` and `node_modules/`, ports 8000 (app) and 5173 (Vite dev/HMR).
- Add a first-run entrypoint that automates the existing `composer setup` flow: `.env` creation, `composer install`, `key:generate`, sqlite touch, `migrate`, `npm install`.
- Add cross-platform command wrappers (`artisan`/`artisan.bat`, `composer`/`composer.bat`) that proxy into the container, so commands look almost identical to vanilla Laravel tutorials and work in cmd, PowerShell, and bash.
- Add `.gitattributes` enforcing LF for shell scripts and CRLF for batch files (prevents container breakage from Windows git line-ending conversion).
- Add `.dockerignore` to keep build context small.
- Gate Vite's `usePolling` file-watching behind an env var (enabled in compose) so HMR works through Windows bind mounts without penalizing Linux/macOS hosts.
- Document quickstart and troubleshooting (incl. hardware virtualization prerequisite) in a Docker section of the README.

## Capabilities

### New Capabilities

- `docker-dev-environment`: Core containerized dev environment — one-command startup, first-run provisioning, pinned toolchain versions, and data persistence across container restarts.
- `docker-command-wrappers`: Cross-platform wrapper scripts (`artisan`, `composer`) that run Laravel/Composer commands inside the container from cmd, PowerShell, and bash.
- `docker-windows-compat`: Windows-contributor guarantees — line-ending integrity, Vite HMR over bind mounts on Windows, and documented troubleshooting for Windows-specific failures.

### Modified Capabilities

(none — no existing specs)

## Impact

- New files: `Dockerfile`, `docker-compose.yml`, `docker/entrypoint.sh`, `artisan(.bat)`, `composer(.bat)` wrappers, `.dockerignore`, `.gitattributes`, README Docker section.
- Modified: `vite.config.js` (host/HMR/polling settings, env-gated).
- Unaffected: application code, routes, database schema, CI (none exists yet).
- Prerequisite for contributors: Docker Desktop (per-user install auto-enables WSL2; no WSL knowledge required).
