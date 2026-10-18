# Design

## Context

Greenfield Laravel 13 project (PHP `^8.3`, SQLite, Vite 8 + Tailwind 4, `artisan dev` for the dev loop). The `composer.json` `setup` script already encodes the intended onboarding flow. Contributors are beginners, mostly on plain Windows (cmd/PowerShell; Docker Desktop's default WSL2 backend runs transparently in the background — they never touch a WSL terminal). Docker 29.x + Compose v5.x are available on the maintainer's machine for testing. Motivation is in proposal.md; requirements are in `specs/`.

## Goals / Non-Goals

**Goals:**
- `docker compose up` from a fresh clone = working app at `localhost:8000`, first run provisions everything.
- Identical PHP/Composer/Node versions for everyone, forever.
- Wrapper scripts so `artisan`/`composer` commands look like vanilla Laravel in cmd, PowerShell, and bash.
- Developer ergonomics on Windows bind mounts (HMR works; slowness/cryptic errors minimized).

**Non-Goals:**
- Production deployment images/orchestration (no nginx, multi-stage prod build, or registry publishing in this change).
- Databases beyond SQLite (no MySQL/Postgres service).
- CI pipelines, Dev Containers / VS Code devcontainer support.
- Removing local (non-Docker) dev as an option for the maintainer.

## Decisions

**D1 — Single `app` container (PHP + Composer + Node) instead of multi-service.**
`artisan dev` already runs server, queue, Pail, and Vite concurrently inside one environment; a single container mirrors that exactly. *Alternative rejected:* separate `app`/`vite`/`queue` services — more "production-like" but triples compose config, introduces service-to-service hostname concepts, and adds beginner cognitive load for zero dev benefit here.

**D2 — Dev image based on `php:8.3-cli`, not `php-apache` / `php-fpm`.**
`artisan serve` (via `artisan dev`) hosts the app; no web server to configure. Node 22 installed in the same image via NodeSource. Composer installed via the official installer. Extensions: `pdo_sqlite`, `mbstring`, `zip`, `curl`, `git`. *Alternative rejected:* FPM+nginx — needed for prod, noise for dev.

**D3 — Bind-mount the repo; named volumes for `vendor/` and `node_modules/`.**
Host edits sync instantly (spec: dev file synchronization). Deps live in named volumes so ~150k files never cross the slow Windows↔WSL2 filesystem boundary and never appear in the host `git status`. *Alternative rejected:* copying deps into the image — would require an image rebuild on every `composer install`.

**D4 — Entrypoint automates provisioning, idempotently, with loud failures.**
`docker/entrypoint.sh`: check `.env` → `composer install` (if `vendor/` empty or `composer.lock` newer) → `key:generate` (if no key) → touch sqlite + `migrate --force` (if DB empty) → `npm install` (if `node_modules` empty) → `exec php artisan dev --host=0.0.0.0`. Each step echoes a labeled banner; `set -e` halts on failure. Runs on every start but is a no-op after first run. *Alternative rejected:* a one-shot `docker compose run setup` — two commands instead of the promised one.

**D5 — Data persistence: bind-mount `database/` and `storage/` (they're already in the repo tree), and rely on the repo bind mount; `.env` and the sqlite file are created on the host where users can see and back them up.**
`docker compose down` never removes bind-mounted data. *Risk accepted:* `down -v` removes only named volumes (deps), which is the safe thing to lose. Re-provisioning then happens automatically on next `up`.

**D6 — Wrappers: thin 1-line `.bat` + sh passthroughs; no `.ps1`.**
`.bat` runs natively in cmd (`artisan migrate`) and PowerShell (`.\artisan migrate`). PowerShell scripts (`.ps1`) are blocked by default execution policy on stock Windows — avoid entirely. Wrapper pre-checks `docker compose ps` for a running container and prints "run docker compose up first" on failure (spec: container-not-running feedback). Compose v2+ exposes `docker compose exec` identically on Windows and Linux, so one implementation per format suffices. *Alternative rejected:* Makefile (absent on stock Windows) or an npm-based CLI (circular: Node is in the container).

**D7 — Vite config: `server.host: true`, `strictPort: true`, and `watch.usePolling` gated on an env var (`VITE_POLLING`), set to `1` in `docker-compose.yml`.**
Windows bind mounts don't deliver inotify events into WSL2 (documented Vite/WSL2 limitation) — polling is the only reliable fix; keeping it env-gated means bare-metal Linux/macOS runs (maintainer, CI) pay zero polling cost. *Trade-off:* polling uses more CPU and HMR latency is ~1–3s on Windows — documented, with `npm run build -- --watch` as fallback.

**D8 — `.gitattributes` enforces `*.sh text eol=lf` and `*.bat text eol=crlf`.**
The #1 silent killer of Windows-clone Docker setups is git rewriting `entrypoint.sh` to CRLF. Enforced at the git level rather than trusting each contributor's `core.autocrlf` setting.

**D9 — Ports: app `8000` (matches `APP_URL` in `.env.example`), Vite `5173`.**
Both already the Laravel defaults; no doc drift.

## Risks / Trade-offs

- [Windows HMR is polling-based: higher CPU, ~1–3s refresh] → env-gated; README documents `npm run build -- --watch` as the low-CPU fallback.
- [Bind-mount performance on Windows is inherently slower than native (Docker docs recommendation is code inside WSL2)] → acceptable for a beginner "clone and go" flow; README notes that cloning into WSL2 is faster for advanced users but not required.
- [`.bat` files cannot be tested on the maintainer's Linux machine] → wrappers are kept to trivially simple 1–3 lines; failure paths rely on `docker compose`'s own cross-platform output.
- [Machine without VT-x/AMD-V virtualization cannot run Docker at all] → unfixable in software; README troubleshooting documents the symptom (Docker Desktop "WSL2 is not supported" / engine won't start) and the BIOS fix.
- [Port 8000/5173 conflicts on a contributor's machine] → README troubleshooting; ports overridable via compose env (`APP_PORT`).
- [First-run `composer install`/`npm install` adds ~1–3 min before first paint] → progress is echo'd per step; subsequent starts skip install entirely.
- [Laravel 13 `artisan dev` internals could change] → entrypoint invokes `artisan dev` with explicit `--host`; if the subcommand changes, one line in the entrypoint changes.

## Migration Plan

Additive only — no existing behavior changes, no rollback needed beyond deleting the new files. Existing non-Docker workflow (local PHP/Composer) remains fully usable since all additions are new files plus an additive `vite.config.js` block.

## Open Questions

- None blocking. (Prod packaging is explicitly a non-goal and can be its own change later.)
