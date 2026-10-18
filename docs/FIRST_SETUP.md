# First Setup — from fresh clone to running app

Set up FoodBridge on your own machine by following this guide top to bottom. It targets beginners running **Laragon** or **XAMPP** on Windows — besides Git and a fresh clone of this repo, nothing else needs to be installed by hand.

> Already set up before? Skip this guide and start the app — see [Running the app](../README.md#running-the-app) in the README.

### Prerequisites

| Tool | Required version | How to check |
|------|------------------|--------------|
| PHP | 8.3, 8.4, or 8.5 (`^8.3`) | `php -v` |
| Composer | 2.10 or newer (latest 2.x) | `composer -V` |
| Node.js | 24 LTS or newer | `node -v` |
| MySQL | MySQL 8 or MariaDB 10.x | ships with Laragon/XAMPP |

> **Why exact versions?** Everyone on the team must run the same toolchain, or you get "works on my machine" bugs. PHP is the one loose requirement: Laragon/XAMPP let you switch PHP versions freely, and any 8.3–8.5 is accepted (`composer.json` pins `^8.3`). Composer and Node should always be the newest stable releases — the Node minimum is enforced via `package.json` `engines`.

Verify before continuing (each must print a version matching the table):

```powershell
php -v          # PHP 8.3.x / 8.4.x / 8.5.x
composer -V     # Composer 2.10.x or newer
node -v         # v24.x.x or newer
```

If a command isn't recognized, install or fix that tool first — see [Troubleshooting](#troubleshooting).

### Step 1 — Laragon setup (Windows)

1. Install the **full** Laragon edition from [laragon.org/download](https://laragon.org/download.html). It bundles Apache, MySQL/MariaDB, and PHP.
2. **Pick a PHP version:** **Menu → PHP → Version → 8.3.x / 8.4.x / 8.5.x**. (If the version isn't listed yet: **Menu → Tools → Quick add → PHP**, pick a version, then select it as above.)
3. **Enable the required PHP extensions.** Open **Menu → PHP → php.ini**, find each line below, and uncomment it by removing the leading `;`:

   ```ini
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=pdo_mysql
   extension=zip
   ```

   (`openssl`, `tokenizer`, `ctype`, `xml`, and `json` are already enabled by default — no action needed.)
4. **Save** `php.ini`, then restart Laragon (**Stop All → Start All**) so the new extensions load.
5. **Start MySQL:** click **Start All** and confirm Laragon shows MySQL as running.

Verify the extensions loaded — in Laragon's terminal (**Menu → Terminal**):

```powershell
php -m | findstr pdo_mysql mbstring curl fileinfo zip openssl
```

Every name must be listed.

### Step 2 — XAMPP setup (Windows)

1. Install XAMPP from [apachefriends.org](https://www.apachefriends.org/download.html). Each XAMPP release bundles one PHP version — if `php -v` reports older than 8.3, grab a newer build from the [download archive](https://www.apachefriends.org/download.html) and reinstall.
2. **Enable the required PHP extensions.** In the XAMPP Control Panel click **Config → PHP (php.ini)** and uncomment these lines (remove the leading `;`):

   ```ini
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=pdo_mysql
   extension=zip
   ```

   (`openssl`, `tokenizer`, `ctype`, `xml`, and `json` are already enabled by default — no action needed.)
3. **Save** the file, then restart **Apache** in the Control Panel (**Stop → Start**).
4. **Start MySQL:** click **Start** next to MySQL and confirm it shows a running PID.

Verify in the XAMPP **Shell** (or any terminal with XAMPP's PHP on your PATH):

```powershell
php -m | findstr pdo_mysql mbstring curl fileinfo zip openssl
```

### Step 3 — Create the database (phpMyAdmin)

The app expects an **empty** MySQL database named `foodbridge`. Create it in phpMyAdmin — never by hand-editing migrations:

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin) (same URL for Laragon and XAMPP).
2. Click **New** in the left sidebar.
3. Under *Database name*, type `foodbridge` and click **Create**. Leave the collation at its default (`utf8mb4_unicode_ci`) — Laravel migrations create all tables themselves.

The app connects with the stock Laragon/XAMPP credentials already set in `.env.example`: host `127.0.0.1`, port `3306`, user `root`, empty password. Only if you changed MySQL's root password do you need to touch `.env` (next step).

### Step 4 — First run

Open a terminal **in the project folder** and run these in order. Two commands differ per shell — run the Windows or Unix variant:

```powershell
# 1. Install PHP dependencies
composer install

# 2. Create .env from the template
copy .env.example .env        # Windows (cmd)
cp .env.example .env          # Unix / macOS / Git Bash

# 3. Generate the application key
php artisan key:generate

# 4. Create all tables in the MySQL database `foodbridge`
php artisan migrate

# 5. Install frontend dependencies and build assets
npm install
npm run build
```

> Shortcut: `composer setup` runs steps 1–5 in one go (`composer install` → `.env` → `key:generate` → `migrate` → `npm install` → `npm run build`). An existing `.env` is never overwritten.

The app is now ready — start it with the commands in the README's [Running the app](../README.md#running-the-app) section.

### Already set up with SQLite? (upgrade note)

The database default changed from SQLite to MySQL. If your existing `.env` still says `DB_CONNECTION=sqlite`, edit these lines to match — the variable names must be exact:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=foodbridge
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate`. Your tables are recreated in MySQL — old SQLite rows are **not** copied (fine during early development; export/import manually if you need to keep data).

### Troubleshooting

| Symptom (what you actually see) | Fix |
|---|---|
| `'php' is not recognized as an internal or external command` (cmd) or `The term 'php' is not recognized as a cmdlet` (PowerShell) | PHP isn't on your PATH. Run commands from Laragon's **Menu → Terminal** or XAMPP's **Shell**, or add PHP's folder (e.g. `C:\xampp\php`) to PATH and open a new terminal. |
| `'composer' is not recognized as an internal or external command` | Install Composer from [getcomposer.org/download](https://getcomposer.org/download/), then open a **new** terminal. |
| `npm : The term 'npm' is not recognized as a cmdlet` (PowerShell) or `npm: command not found` (Unix) | Install Node.js 24 LTS or newer from [nodejs.org](https://nodejs.org/), then reopen the terminal. |
| `Class "PDO" not found` or `could not find driver` during `php artisan migrate` | The `pdo_mysql` extension is off. Uncomment `extension=pdo_mysql` in `php.ini` (Step 1 or 2) and restart Apache/Laragon. |
| `Call to undefined function mb_strtolower()` | The `mbstring` extension is off. Uncomment `extension=mbstring` in `php.ini` and restart. |
| `Failed to listen on 0.0.0.0:8000` / `Address already in use` | Port 8000 is taken. Run `php artisan serve --port=8080` and open <http://localhost:8080>. |
| `No application encryption key has been specified.` | Run `php artisan key:generate`. |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | Wrong MySQL credentials in `.env` — reset them to the stock block shown in the upgrade note above. |
| `Unknown database 'foodbridge'` when migrating | Create the database in phpMyAdmin first (Step 3) — it must exist before `php artisan migrate`. |
