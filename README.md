# FoodBridge

FoodBridge is an Laravel Web application that connects food donors with organization or individual that need food to reduce food waste.

## First setup

Never set this project up before? Follow **[docs/FIRST_SETUP.md](docs/FIRST_SETUP.md)** — it takes you from a fresh clone to a running app: toolchain versions, Laragon/XAMPP setup, MySQL database creation, first-run commands, and troubleshooting.

## Running the app

Already set up (`.env` exists, dependencies installed, database migrated)? Start everything in **one terminal**:

```powershell
composer run dev
```

That single command runs the Laravel dev server, Vite (hot reload for CSS/JS), the queue worker, and live log streaming — all together, with auto-restart on crash. Then open [http://localhost:8000](http://localhost:8000).

If port 8000 is already in use, stop the command and run `php artisan serve --port=8080`, then use [http://localhost:8080](http://localhost:8080) instead. (Running `npm run dev` and `php artisan serve` in two separate terminals works too, if you prefer.)
