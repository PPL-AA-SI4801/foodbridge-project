# Proposal

## Why

The application has no way to sign in: `routes/web.php` serves a single public welcome page, and none of the 11 tables is reachable from a browser. Every must-have flow in `docs/PRD.md` — posting surplus food, claiming, admin verification — needs an authenticated account with a known role. The ERD already carries `users.role` and `users.status_akun`, and legal documents live in `verification_documents`, so the entry point can be built on the existing schema rather than new tables.

## What Changes

- Add registration as Donor or Penerima: account fields, the matching profile row (`donor_profiles` or `recipient_profiles`), and one legality document in `verification_documents`, written in a single transaction. New accounts start at `status_akun = menunggu` and cannot sign in until approved.
- Add sign-in, sign-out, and password reset on Laravel's built-in session guard, scaffolded with Laravel Breeze (Blade stack) and adapted to the ERD users table.
- Add an account-status gate: only `aktif` accounts obtain a session; `menunggu`, `ditolak`, and `diblokir` are refused with distinct messages, and an account that stops being active loses access on its next request instead of keeping an open session.
- Add `diblokir` as a fourth `users.status_akun` value in `docs/DB.md` §5, because the PRD lets an Admin block an account after investigation while `ditolak` means a rejected registration.
- Add backed enums for `users.role` (`donor`, `penerima`, `administrator`) and `users.status_akun`, cast on the `User` model so role and status comparisons cannot be misspelled.
- Add role-based access: a `role` middleware and one admin-only placeholder area, establishing where each role's pages will live.
- Seed one active account per role so sign-in and access control can be exercised before the admin verification screen exists.
- **BREAKING** (local development only): registration and sign-in follow the ERD users schema, so there is no remember-me and no email verification; the table has neither `remember_token` nor `email_verified_at`.
- Out of scope, recorded as decisions: admin verification/approval screen, account blocking screen, profile editing, email verification, two-factor authentication, remember-me, Dusk end-to-end tests, and a dedicated test database.

## Capabilities

### New Capabilities

- `user-authentication`: registration as a donor or recipient with a legality document, sign-in gated by account status, sign-out, and password reset.
- `role-based-access`: role-aware access control — every account has exactly one role, protected areas are declared per role, and the admin area is restricted to administrators.

### Modified Capabilities

(none — no existing spec covers authentication or access control)

## Impact

- New: `app/Enums/{Role,AccountStatus}.php`, `app/Http/Controllers/Auth/*`, `app/Http/Requests/Auth/*`, `app/Http/Middleware/EnsureAccountIsActive.php`, `app/Http/Middleware/EnsureUserHasRole.php`, dashboard and admin placeholder controllers and views, auth Blade views and layouts, shared form components.
- Modified: `routes/web.php` plus `routes/auth.php`, `bootstrap/app.php` (guest/user redirects and middleware aliases), `app/Models/User.php` (enum casts and helpers), `database/seeders/DatabaseSeeder.php`, `package.json` / `resources/css/app.css` / `vite.config.js` (Breeze install reconciled with Tailwind 4 and Vite 8), `docs/DB.md` (account status values), `docs/FIRST_SETUP.md` (seeded accounts).
- Dependencies: `laravel/breeze` as a dev dependency, `alpinejs` and `@tailwindcss/forms` on the frontend. No production Composer dependency — authentication runs on the framework's session guard.
- Data: legality documents are stored on the existing private `local` disk (`storage/app/private`), so no new disk, environment variable, or `storage:link` is required.
- Deferred consequence: legality documents stay at `status = menunggu`, and newly registered accounts can only be activated manually, until the admin verification change lands.
