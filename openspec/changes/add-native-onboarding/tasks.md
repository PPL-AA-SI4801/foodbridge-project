# Tasks

## 1. MySQL Default Configuration

- [x] 1.1 Switch the active DB block in `.env.example` to `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=foodbridge`, `DB_USERNAME=root`, `DB_PASSWORD=` (empty), with SQLite commented out as a labeled non-default alternative; verify `grep -A8 '^DB_CONNECTION' .env.example` shows MySQL active and no uncommented sqlite line
- [x] 1.2 Remove the SQLite `touch database/database.sqlite` line from `composer.json`'s `post-create-project-cmd` and verify `composer validate` still passes with no syntax errors

## 2. README Onboarding Section

- [x] 2.1 Add the prerequisites section: exact version table (PHP per `composer.json` `^8.3` — any 8.3–8.5, since Laragon/XAMPP users switch freely; Composer 2.10+; Node.js 24 LTS or newer per `package.json` `engines`; MySQL 8 or MariaDB 10.x) with verification commands (`php -v`, `composer -V`, `node -v`) and the "why exact versions" rationale; verify every listed version matches `composer.json`/`package.json` requirements
- [x] 2.2 Add the Laragon setup path (PHP version selection menu path, required extension checklist with `php.ini` enable+restart steps, MySQL started) and verify each documented menu path/extension name matches current Laragon behavior
- [x] 2.3 Add the XAMPP setup path (PHP version install/switch, `php.ini` extensions to uncomment, Apache restart note, MySQL via Control Panel) and verify each documented `php.ini` line exists in stock XAMPP
- [x] 2.4 Add the phpMyAdmin database-creation step (`http://localhost/phpmyadmin`, create `foodbridge` with `utf8mb4_unicode_ci`) and verify the documented database name and collation match `.env.example` (`DB_DATABASE=foodbridge`) and design D3
- [x] 2.5 Add the first-run command sequence (composer install → cp/copy `.env` → key:generate → migrate → npm install → npm run dev/build → artisan serve → URL) with Windows **and** Unix variants per differing command; verify the command list is complete and ordered correctly against the `composer setup` script
- [x] 2.6 Add the upgrade note for existing contributors (old `.env` still on sqlite: switch DB block to MySQL defaults, re-run migrate) and verify it names the exact variables to change
- [x] 2.7 Add the symptom-first troubleshooting table (`php`/`npm`/`composer` not recognized, missing-extension/PDO errors, port 8000 in use with `--port=8080` alternative, missing `APP_KEY`) and verify each symptom string matches real error output
