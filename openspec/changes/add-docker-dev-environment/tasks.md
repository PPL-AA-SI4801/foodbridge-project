# Tasks

## 1. Image and Container Foundation

- [ ] 1.1 Create `Dockerfile` (php:8.3-cli base, extensions `pdo_sqlite`/`mbstring`/`zip`/`curl`/`git`, Composer, Node 22) and verify `docker build -t foodbridge-dev .` completes successfully
- [ ] 1.2 Create `.dockerignore` (exclude `vendor/`, `node_modules/`, `.git`, `database/database.sqlite`) and verify the build context sent to Docker no longer contains them (`docker build` output shows a small context)
- [ ] 1.3 Create `docker-compose.yml` (single `app` service, repo bind mount, named volumes for `vendor/` and `node_modules/`, ports 8000 + 5173, `VITE_POLLING=1`) and verify `docker compose config` renders without errors

## 2. First-Run Provisioning

- [ ] 2.1 Create `docker/entrypoint.sh` with idempotent steps (`.env` copy, `key:generate`, sqlite touch, `composer install`, `npm install`, `migrate --force`), each echo-labeled with `set -e`, and verify with `bash -n docker/entrypoint.sh` that it parses cleanly
- [ ] 2.2 Wire the entrypoint into the image/compose and verify the one-command startup scenario: delete `.env`, `vendor/`, `node_modules/`, and `database/database.sqlite`, run `docker compose up`, and confirm the app serves at `http://localhost:8000` with all provisioning steps logged
- [ ] 2.3 Verify data persistence: with the app seeded, run `docker compose down && docker compose up` and confirm the SQLite data is intact and provisioning steps no-op on second start

## 3. Cross-Platform Wrappers

- [ ] 3.1 Create `artisan` (sh) and `artisan.bat` wrappers that pre-check the container is running (friendly "run docker compose up first" message otherwise) and proxy args to `php artisan` inside the container; verify `./artisan migrate` and exit-code pass-through (`./artisan --version`) work from bash
- [ ] 3.2 Create `composer` (sh) and `composer.bat` wrappers with the same pattern; verify `./composer --version` reports the container's pinned Composer version and `./composer require` (then remove) updates `composer.json` on the host
- [ ] 3.3 Make wrappers executable (`chmod +x`) and create `.gitattributes` (`*.sh text eol=lf`, `*.bat text eol=crlf`); verify `git check-attr eol -- docker/entrypoint.sh artisan.bat` reports the forced endings

## 4. Vite HMR for Windows Bind Mounts

- [ ] 4.1 Update `vite.config.js`: `server.host: true`, `strictPort: true`, and `watch.usePolling` gated on `VITE_POLLING` env var (preserving the existing `storage/framework/views` ignore); verify `VITE_POLLING=1 docker compose up` + editing `resources/css/app.css` on the host triggers an HMR update in the browser
- [ ] 4.2 Verify the no-polling path: unset `VITE_POLLING` and confirm Vite starts without polling (no config errors) on the bare-metal workflow

## 5. Documentation

- [ ] 5.1 Add a Docker section to `README.md`: prerequisites (Docker Desktop per-user install, virtualization note), quickstart (`git clone` → `docker compose up` → open `http://localhost:8000`), wrapper cheat sheet in both cmd and PowerShell syntax, and stop/down commands; verify every command in the section is copy-paste runnable as written
- [ ] 5.2 Add the troubleshooting subsection (virtualization disabled in BIOS, port 8000/5173 in use, `npm run build -- --watch` low-CPU fallback, optional WSL2-clone performance tip); verify each documented symptom/fix matches actual error output observed during testing

## 6. Integration Verification

- [ ] 6.1 Full cold-start rehearsal on a clean state: `docker compose down -v`, remove `.env`/`vendor`/`node_modules`/sqlite, `docker compose up`, and verify the app loads with working HMR, `./artisan test` passes inside the container, and `./artisan migrate` works via wrapper
- [ ] 6.2 Confirm non-Docker workflow still intact: `composer.json` scripts and `vite.config.js` changes do not break `php artisan dev` / `npm run dev` run directly on the host
