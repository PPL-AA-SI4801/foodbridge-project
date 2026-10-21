# FoodBridge

FoodBridge is a Laravel Web application that connects food donors with organization or individual that need food to reduce food waste.

## First setup

Never set this project up before? Follow **[docs/FIRST_SETUP.md](docs/FIRST_SETUP.md)** — it takes you from a fresh clone to a running app: toolchain versions (PHP 8.3/8.4/8.5, Composer 2.2+, Node 24+), Laragon/XAMPP setup, MySQL database creation, first-run commands, and troubleshooting.

> `vendor/` and `node_modules/` are gitignored, so a fresh clone has no dependencies installed. Run `composer install` (or `composer setup` for the whole first run) before anything else.

## Running the app

Already set up (`.env` exists, dependencies installed, database migrated)? Start everything in **one terminal**:

```powershell
composer run dev
```

That single command runs the Laravel dev server, Vite (hot reload for CSS/JS), and the queue worker — all together, with auto-restart on crash. Live log streaming (`php artisan pail`) is added automatically on Linux/macOS; on Windows it is skipped because the `pcntl` extension does not exist there — run `php artisan pail` in a second terminal if you want it. Then open [http://localhost:8000](http://localhost:8000).

If port 8000 is already in use, stop the command and run `php artisan serve --port=8080`, then use [http://localhost:8080](http://localhost:8080) instead. (Running `npm run dev` and `php artisan serve` in two separate terminals works too, if you prefer.)
