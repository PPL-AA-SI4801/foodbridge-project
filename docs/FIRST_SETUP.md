# First Setup — from fresh clone to running app

Set up FoodBridge on your own machine by following this guide top to bottom. It targets beginners running **Laragon** or **XAMPP** on Windows — besides Git and a fresh clone of this repo, nothing else needs to be installed by hand.

> Already set up before? Skip this guide and start the app — see [Running the app](../README.md#running-the-app) in the README.

### Prerequisites

| Tool | Required version | How to check |
|------|------------------|--------------|
| PHP | 8.3, 8.4, or 8.5 | `php -v` |
| Composer | 2.10 or newer (latest 2.x) | `composer -V` |
| Node.js | 24 LTS or newer | `node -v` |
| MySQL | MySQL 8 or MariaDB 10.x | ships with Laragon/XAMPP |

> **Where these numbers come from** — they are read from the repo, not guessed:
>
> | Requirement | Source of truth |
> |-------------|-----------------|
> | PHP `^8.3` | `composer.json` → `require.php` (and `config.platform.php = "8.3.3"`) |
> | Composer 2.10+ | team policy (latest 2.x). Hard technical floor is 2.2 — `laravel/framework` requires `composer-runtime-api ^2.2` |
> | Node 24+ | `package.json` → `engines.node: ">=24"` |
> | MySQL 8 / MariaDB 10.x | `.env.example` (`DB_CONNECTION=mysql`) + `config/database.php` |
>
> **Why exact versions?** Everyone on the team must run the same toolchain, or you get "works on my machine" bugs. PHP is the one loose requirement: Laragon/XAMPP let you switch PHP versions freely, and **any of 8.3, 8.4 or 8.5 is accepted** — `composer.json` pins `^8.3`, and `config.platform.php` is pinned to `8.3.3` so the committed `composer.lock` always resolves against the *lowest* supported PHP. That is what lets one lock file install cleanly on 8.3, 8.4 and 8.5 alike. Composer and Node should always be the newest stable releases.

Verify before continuing (each must print a version matching the table):

```powershell
php -v          # PHP 8.3.x / 8.4.x / 8.5.x
composer -V     # Composer 2.10.x or newer
node -v         # v24.x.x or newer
```

If a command isn't recognized, install or fix that tool first — see [Troubleshooting](#troubleshooting).

#### PHP extensions

The locked packages require these PHP extensions: `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `phar`, `session`, `tokenizer`, `xml`, `xmlwriter` — plus `pdo_mysql` to talk to MySQL. Most are enabled by default; the ones Windows builds commonly ship switched off are covered in Steps 1–2.

After picking your PHP version, let Composer verify the whole list for you:

```powershell
composer check-platform-reqs
```

Every row must say `success`.

### Step 1 — Laragon setup (Windows)

1. Install the **full** Laragon edition from [laragon.org/download](https://laragon.org/download.html). It bundles Apache, MySQL/MariaDB, and PHP.
2. **Pick a PHP version:** **Menu → PHP → Version → 8.3.x / 8.4.x / 8.5.x**. (If the version isn't listed yet: **Menu → Tools → Quick add → PHP**, pick a version, then select it as above.) Any of the three works with the committed lock file.
3. **Enable the required PHP extensions.** Open **Menu → PHP → php.ini**, find each line below, and uncomment it by removing the leading `;`:

   ```ini
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=pdo_mysql
   extension=zip
   ```

   (`openssl`, `tokenizer`, `ctype`, `xml`, and `json` are already enabled by default — no action needed.)
4. **Save `php.ini`, then restart Laragon (**Stop All → Start All**) so the new extensions load.
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
3. **Save the file, then restart **Apache** in the Control Panel (**Stop → Start**).
4. **Start MySQL:** click **Start** next to MySQL and confirm MySQL shows a running PID.

Verify in the XAMPP **Shell** (or any terminal with XAMPP's PHP on your PATH):

```powershell
php -m | findstr pdo_mysql mbstring curl fileinfo zip openssl
```

### Step 3 — Create the database (phpMyAdmin)

The app expects an **empty** MySQL database named `foodbridge`. Create it in phpMyAdmin — never by hand-editing migrations:

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin) (same URL for Laragon and XAMPP).
2. Click **New** in the left sidebar.
3. Under *Database name*, type `foodbridge` and click **Create**. Leave the collation at its default (`utf8mb4_unicode_ci`) — Laravel migrations create all tables themselves.

The app connects with the stock Laragon/XAMPP credentials already set in `.env.example`: host `127.0.0.1`, port `3306`, user `root`, empty password, database `foodbridge`. Only if you changed MySQL's root password do you need to touch `.env` (next step).

### Step 4 — First run

Open a terminal **in the project folder** and run these in order. Two commands differ per shell — run the Windows or Unix variant:

```powershell
# 1. Install PHP dependencies (vendor/ is gitignored — this is required on every fresh clone)
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

> Shortcut: `composer setup` runs steps 1–5 in one go (`composer install` → `.env` → `key:generate` → `migrate --force` → `npm install --ignore-scripts` → `npm run build`). It does **not** create the database (Step 3) — do that first. An existing `.env` is never overwritten.
>
> Note: no `package-lock.json` is committed, so `npm install` resolves the newest versions matching `package.json` — frontend dependency versions can differ between teammates. Commit the lockfile if you want them identical.

The app is now ready — start it with `composer run dev` (see [Running the app](../README.md#running-the-app)) and open <http://localhost:8000>.

### What `composer run dev` actually starts

`composer run dev` runs `php artisan dev`, which starts four processes and restarts any of them if it crashes:

| Process | Command | Notes |
|---------|---------|-------|
| server | `php artisan serve` | <http://localhost:8000> |
| queue | `php artisan queue:listen --tries=1 --timeout=0` | Needs the `jobs` table — `php artisan migrate` creates it |
| vite | `npm run dev` | Hot reload for CSS/JS; needs `npm install` |
| logs | `php artisan pail --timeout=0` | **Linux/macOS only** — skipped on Windows, where the `pcntl` extension does not exist. Run `php artisan pail` in a second terminal instead. |

### Database migrations

`php artisan migrate` builds the whole schema from the files in `database/migrations/`. Apart from the empty `foodbridge` database itself (Step 3), nothing has to be created by hand in phpMyAdmin.

**What gets created**

| Table | Comes from |
|-------|------------|
| `users`, `donor_profiles`, `recipient_profiles`, `verification_documents`, `food_categories`, `food_posts`, `food_requests`, `claims`, `courier_details`, `food_reviews`, `notifications` | the ERD schema documented in [`docs/DB.md`](DB.md) |
| `password_reset_tokens`, `sessions` | Laravel infrastructure, created together with `users` |
| `cache`, `cache_locks` | Laravel cache (`CACHE_STORE=database`) |
| `jobs`, `job_batches`, `failed_jobs` | Laravel queue (`QUEUE_CONNECTION=database`) |
| `migrations` | bookkeeping — which migrations already ran |

**If your database was created before this schema** (you ran the migrations earlier, or you are coming from the old SQLite setup), `php artisan migrate` is not enough. Laravel runs a migration only once — it is recorded in the `migrations` table — so the reshaped `users` table is never picked up. Rebuild the schema instead:

```powershell
php artisan migrate:fresh          # drop every table and re-run all migrations
php artisan migrate:fresh --seed   # ... and insert the three demo accounts
```

> `migrate:fresh` **deletes all data** in the `foodbridge` database. Only run it on a development database.

**The `users` table** follows the ERD, not Laravel's default: it has `nama`, `email`, `password`, `no_hp`, `role`, and `status_akun` — there is no `name`, `email_verified_at`, or `remember_token` column.

**Check the result**

```powershell
php artisan migrate:status
```

Every migration must show `Ran`, and the eleven application tables from the table above must exist in phpMyAdmin afterwards.

**The demo accounts** — `php artisan migrate --seed` (or `migrate:fresh --seed`) creates one `aktif` account per role. All three use the password `password`:

| Role | Email | Profile |
|------|-------|---------|
| Donor | `donor@foodbridge.test` | Toko Donor Demo |
| Penerima | `penerima@foodbridge.test` | Panti Penerima Demo |
| Administrator | `admin@foodbridge.test` | — |

Sign in at <http://localhost:8000/login>; a successful sign-in lands on `/dashboard`, and signing out from the navigation returns you to the welcome page.

Sign-in is refused while `status_akun` is `menunggu` (waiting for verification), `ditolak` (registration rejected), or `diblokir` (blocked after approval), each with its own message on the sign-in page. Only new registrations start as `menunggu` — the seeded accounts are already `aktif`.

Forgot a password? **Forgot your password?** emails a reset link. In development `MAIL_MAILER=log`, so the link is written to `storage/logs/laravel.log` instead of being sent.

### Registration

Open <http://localhost:8000/register> to create a donor or recipient account:

1. Choose **Donor** (business) or **Penerima** (institution); the form shows that role's profile fields.
2. Fill in the account and profile details, choose a document type, and upload one legality document.
3. Submit. You return to the sign-in page with a notice that the account is waiting for verification.

New accounts are always created with `status_akun = menunggu` and **cannot sign in until an administrator verifies them**. The sign-in page says the account is waiting for verification. Admin approval screens are a later change, so activate a new account by hand for now:

```powershell
php artisan tinker --execute="App\Models\User::where('email', 'new-donor@example.com')->update(['status_akun' => 'aktif']);"
```

Replace the email address with the one you registered, then sign in normally. Running the same command with `'ditolak'` or `'diblokir'` lets you test the other refusal messages.

**Upload rules:** one PDF, JPG, JPEG, or PNG file, at most **2 MB**. Legality documents are stored privately under `storage/app/private/verification-documents/{user_id}/` and are never served from `public/`. Email addresses and business/institution names must be unique, and passwords need at least 8 characters.

**Upload errors:** PHP rejects a file larger than `upload_max_filesize` before Laravel validates it, so such a file fails with "The dokumen failed to upload." instead of the 2 MB message. If you need larger scans, raise both `upload_max_filesize` and `post_max_size` in `php.ini` (keep `post_max_size` above `upload_max_filesize`), restart the web server, and update the `max:2048` rule in `app/Http/Requests/Auth/RegisterRequest.php` to the new ceiling. The stock values used here are `upload_max_filesize=2M` and `post_max_size=8M`.

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

Then run `php artisan migrate`. Your tables are recreated in MySQL — old SQLite rows are **not** copied (fine during early development; export/import manually if you need to keep data). If MySQL already holds an older schema, use `php artisan migrate:fresh` instead — see [Database migrations](#database-migrations).

### Troubleshooting

| Symptom (what you actually see) | Fix |
|---|---|
| `Warning: require(...vendor/autoload.php): Failed to open stream: No such file or directory` / `Fatal error: Failed opening required '.../vendor/autoload.php'` | Dependencies are not installed. `vendor/` is in `.gitignore`, so a fresh clone never has it — run `composer install` in the project folder. |
| `Your lock file does not contain a compatible set of packages` + `symfony/... requires php >=8.4.1` | Your PHP is older than the lock expects. Check `php -v`; the committed lock supports **PHP 8.3, 8.4 and 8.5**. Switch the PHP version in Laragon (**Menu → PHP → Version**) and re-run `composer install`. |
| `Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.3"` | You are below PHP 8.3. Upgrade PHP, then re-run `composer install`. |
| `Warning: The lock file is not up to date with the latest changes in composer.json` | Someone changed `composer.json` without re-locking. Run `composer update "symfony/*" --with-all-dependencies` (keeps the PHP 8.3-compatible dependency set) and commit the updated `composer.lock`. |
| `The zip extension and unzip/7z commands are both missing` during `composer install` | Composer cannot unpack packages. Enable `extension=zip` in `php.ini`, or install `unzip`. |
| `'php' is not recognized as an internal or external command` (cmd) or `The term 'php' is not recognized as a cmdlet` (PowerShell) | PHP isn't on your PATH. Run commands from Laragon's **Menu → Terminal** or XAMPP's **Shell**, or add PHP's folder (e.g. `C:\xampp\php`) to PATH and open a new terminal. |
| `'composer' is not recognized as an internal or external command` | Install Composer from [getcomposer.org/download](https://getcomposer.org/download/), then open a **new** terminal. |
| `npm : The term 'npm' is not recognized as a cmdlet` (PowerShell) or `npm: command not found` (Unix) | Install Node.js 24 or newer from [nodejs.org](https://nodejs.org/), then reopen the terminal. |
| `concurrently: not found` / `artisan dev` complains that `node_modules` is missing | Frontend dependencies are not installed — run `npm install`. |
| `Class "PDO" not found` or `could not find driver` during `php artisan migrate` | The `pdo_mysql` extension is off. Uncomment `extension=pdo_mysql` in `php.ini` (Step 1 or 2) and restart Apache/Laragon. |
| `Call to undefined function mb_strtolower()` | The `mbstring` extension is off. Uncomment `extension=mbstring` in `php.ini` and restart. |
| `Failed to listen on 0.0.0.0:8000` / `Address already in use` | Port 8000 is taken. Run `php artisan serve --port=8080` and open <http://localhost:8080>. |
| `No application encryption key has been specified.` | Run `php artisan key:generate`. |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | Wrong MySQL credentials in `.env` — reset them to the stock block shown in the upgrade note above. |
| `Unknown database 'foodbridge'` when migrating | Create the database in phpMyAdmin first (Step 3) — it must exist before `php artisan migrate`. |
| `SQLSTATE[42S22]: Column 'nama' doesn't exist` (or any missing `nama`, `no_hp`, `role`, `status_akun` column) | Your database still has the old Laravel `users` table. The migration file was changed and Laravel runs a migration only once — rebuild the schema with `php artisan migrate:fresh` (see [Database migrations](#database-migrations)). It deletes all data. |
