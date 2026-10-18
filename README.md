# FoodBridge

FoodBridge is an AI-driven Laravel application that connects food donors with NGOs and charity organizations to reduce food waste.

## First setup

Never set this project up before? Follow **[docs/FIRST_SETUP.md](docs/FIRST_SETUP.md)** — it takes you from a fresh clone to a running app: toolchain versions, Laragon/XAMPP setup, MySQL database creation, first-run commands, and troubleshooting.

## Running the app

Already set up (`.env` exists, dependencies installed, database migrated)? Start the two dev servers, each in its own terminal, both left running:

```powershell
# Terminal 1 — Vite dev server (hot reload for CSS/JS)
npm run dev

# Terminal 2 — Laravel dev server
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000). If port 8000 is already in use, run `php artisan serve --port=8080` and use [http://localhost:8080](http://localhost:8080) instead.
