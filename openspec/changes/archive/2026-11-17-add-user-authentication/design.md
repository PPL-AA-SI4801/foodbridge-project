# Design

## Context

Laravel 13.38 on PHP `^8.3` (local WSL runs 8.5), MySQL, Blade views with Tailwind 4 + Vite 8. Authentication is session-based (`SESSION_DRIVER=database`, `sessions` table already migrated), mail is `MAIL_MAILER=log`, and the default disk is `local` whose root is `storage/app/private`.

The schema is already in place and constrains the approach: `users` has `nama` (not `name`), `role`, `status_akun`, and no `remember_token`, no `email_verified_at`, and no timestamps at all (`#[WithoutTimestamps]`). Profiles and `verification_documents` are migrated, with `lat`/`lng` nullable. Requirements are in `specs/`; motivation is in `proposal.md`.

`bootstrap/app.php` currently registers no middleware customisation, so Laravel 13's `Handler::unauthenticated()` returns a bare `401` when no redirect target is configured — verified in `vendor/laravel/framework`; there is no `route('login')` fallback.

## Goals / Non-Goals

**Goals:**
- Framework-native session authentication with as little custom security code as possible.
- Registration that writes account, profile, and legality document atomically.
- One obvious place per concern: credentials/status in the sign-in request, ongoing status in one middleware, roles in one middleware.
- Keep the repo's Tailwind 4 + Vite 8 toolchain intact.

**Non-Goals:**
- API tokens, Sanctum, social login, 2FA, remember-me, email verification.
- Profile editing, admin verification/blocking screens, notifications.
- A test database, Dusk, or CI.

## Decisions

**D1 — Scaffold with Laravel Breeze (Blade stack), run on the built-in session guard.**
The maintainer asked for Breeze, and it supplies battle-tested controllers, requests, views, and throttling on the same guard the framework ships. *Alternatives rejected:* hand-written equivalents (more security surface for no gain in a Blade app), Laravel Fortify (adds a config layer for features we explicitly excluded: 2FA, email verification, remember-me, API), Laravel 13 starter kits (React/Svelte/Vue/Livewire only — no Blade option). `laravel/breeze` stays a dev dependency as scaffolding provenance; its output is committed and customised, so it must not be re-run.

**D2 — Keep Tailwind 4; do not accept Breeze's Tailwind 3 downgrade.**
`breeze:install blade` adds `tailwindcss ^3.1`, `postcss`, `autoprefixer`, copies `tailwind.config.js`, `postcss.config.js`, `vite.config.js`, overwrites `resources/css/app.css` and `resources/js/app.js`, and rewrites `routes/web.php`. Install order therefore is:

1. `composer require laravel/breeze --dev`
2. `php artisan breeze:install blade --no-interaction`
3. Restore from git: `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`.
4. Delete `tailwind.config.js` and `postcss.config.js`; remove `tailwindcss@^3`, `postcss`, `autoprefixer` from `package.json`.
5. Keep `alpinejs` (Breeze's navigation and modal components need it) and `@tailwindcss/forms`, loaded the Tailwind 4 way: `@plugin '@tailwindcss/forms';` at the top of `resources/css/app.css`.
6. Re-run `npm install && npm run build` after step 3–5.
7. Rewrite `routes/web.php` (`/` welcome, `/dashboard`, `/admin`, `require routes/auth.php`).

*Alternative rejected:* let Breeze replace the toolchain — it would downgrade the whole application's CSS pipeline for a scaffold we only keep the PHP parts of.

**D3 — Reconcile Breeze with the ERD instead of changing the ERD.**
Breeze assumes `name`, `remember_token`, `email_verified_at`, and an editable profile. We keep `nama` (no migration), drop remember-me (no column), remove email verification (no column, and the PRD has no such requirement), and delete the profile/password-update screens (profile management is PRD FR-02, a later change). Password reset stays: `password_reset_tokens` exists and `Illuminate\Foundation\Auth\User` already uses `CanResetPassword`. The navigation must stop referencing `profile.edit`, or every authenticated page errors.

**D4 — Configure redirects centrally in `bootstrap/app.php`.**
Add `->redirectGuestsTo('/login')` and `->redirectUsersTo('/dashboard')` in `withMiddleware`, because Laravel 13 no longer redirects guests to a conventional `login` route by itself. *Alternative rejected:* per-route `->redirectGuestsTo()` calls — duplicated and easy to miss.

**D5 — Enforce account status at sign-in and on every request.**
Sign-in path: validate → throttle → `Auth::attempt` → inspect `status_akun` → on success `session()->regenerate()`; on any non-`aktif` status, immediately `Auth::logout()`, invalidate the session, and return a status-specific message. A middleware appended to the `web` group no-ops for guests and logs out authenticated accounts whose status is no longer `aktif`, so a block takes effect on the next request. *Alternatives rejected:* status check only at sign-in (a blocked account keeps its session until it signs out), status check only in middleware (the sign-in form would appear to succeed and then bounce, with a generic message).

**D6 — Model role and status as backed enums, keep the columns as strings.**
`App\Enums\Role` (`donor`, `penerima`, `administrator`) and `App\Enums\AccountStatus` (`menunggu`, `aktif`, `ditolak`, `diblokir`), cast in `User::casts()`. No migration: the ERD stores `varchar` and a PHP enum keeps comparisons and messages type-safe. *Alternative rejected:* MySQL `enum` columns — inflexible, and the ERD source of truth says string.

**D7 — Registration is one transaction, with file cleanup on failure.**
Validate (unique email, unique `nama_usaha`/`nama_lembaga`, password rules, document type/size) → store the document on the `local` disk → `DB::transaction` creating `users` (`status_akun = menunggu`), the role's profile, and the `verification_documents` row (`status = menunggu`) → on any exception, delete the stored file and rethrow. On success, do not sign in: redirect to `/login` with a "waiting for verification" status. Account creation uses the `hashed` cast already on the model.

**D8 — One registration page adapts to the chosen role.**
A role radio (`donor`/`penerima`) toggles the role-specific field groups with Alpine (`x-show`); the server validates and persists only the chosen role's profile, so a crafted request cannot create both. `lat`/`lng` are omitted: both columns are nullable and the map picker is a separate change. Document type is a select (`nib`, `akta_pendirian`, `izin_operasional`, `lainnya`) because the ERD leaves `jenis_dokumen` as free text.

**D9 — Store legality documents on the existing private `local` disk.**
Path `verification-documents/{user_id}/{uuid}.{ext}`, accepted types `pdf`, `jpg`, `jpeg`, `png`, maximum **2 MB**. The 2 MB ceiling matches this environment's `upload_max_filesize=2M`, so stock PHP accepts the file; a larger ceiling would fail before validation runs. No `storage:link`, no new disk, no environment variable. *Alternative rejected:* the `public` disk — legality documents are sensitive, and the admin screen (later) will stream them through an authorised route.

**D10 — New accounts stay `menunggu`; activation is manual until the admin screen exists.**
This is the deliberate cost of deferring verification. `docs/FIRST_SETUP.md` will state that a freshly registered account cannot sign in and that seed accounts exist for demonstration. *Alternative rejected:* a temporary `account:activate` command — a throwaway interface that would be deleted by the next change; `php artisan tinker` covers the gap.

**D11 — Route-level role checks via a `role` middleware alias.**
`EnsureUserHasRole` accepts one or more roles, aborts `403` when the authenticated account's role is not in the list, and is registered as `role` in `bootstrap/app.php`. `/dashboard` is open to every authenticated account; `/admin` is behind `role:administrator`. *Alternative rejected:* Gates/Policies — the right tool once role checks depend on a specific record, heavier than needed for area-level access.

**D12 — Seed one `aktif` account per role.**
`donor@foodbridge.test`, `penerima@foodbridge.test`, `admin@foodbridge.test`, password `password`, each with the matching profile. The admin seed is the only way to see the admin area before verification screens exist. `UserFactory` is updated to produce valid `nama`/`role`/`status_akun` so future tests do not fail on missing columns.

**D13 — Drop Breeze's database-backed feature tests for this change.**
They need a working test database, and the maintainer explicitly deferred creating one; leaving them in would turn `php artisan test` red (no `pdo_sqlite` driver here). The existing two non-DB tests must keep passing. The tests return with the test-database change. *Alternative rejected:* committing failing/skipped tests — a red suite hides real regressions.

**D14 — Password reset uses Breeze's flow unchanged.**
Forgot/reset controllers, views, and throttling are kept; the token table already exists and the log mailer writes the link to `storage/logs/laravel.log`, which is adequate for local development and documented.

## Risks / Trade-offs

- [Breeze overwrites `routes/web.php`, `vite.config.js`, `app.css`, `app.js` and adds Tailwind 3] → fixed by the ordered install in D2; after install run `git status --short`, `php artisan route:list`, and `npm run build` before touching PHP.
- [Breeze's navigation references `profile.edit`, which D3 deletes] → trim `navigation.blade.php` to Dashboard, Admin (administrators only), and Logout; smoke-test every auth page.
- [`@tailwindcss/forms` may not be v4-compatible through `@plugin`] → if the build fails, drop the plugin and keep Breeze's utility classes; inputs remain usable, only base styling is lost.
- [No email verification: an attacker can register with someone else's address] → accepted; access still requires admin approval of the legality document, and the reset mail goes to the address owner.
- [New accounts cannot sign in until manually activated, which can look like a bug] → sign-in refusal names the `menunggu` status, registration's success notice says approval is pending, and `docs/FIRST_SETUP.md` documents seed accounts.
- [Uploads over 2 MB fail before validation with a confusing "failed to upload" error] → validation ceiling matches PHP's configured limit; the setup guide's troubleshooting section documents raising `upload_max_filesize`/`post_max_size` if larger scans are needed.
- [Status changes mid-session are only detected on the next request] → acceptable for a web session model; the middleware runs on every web request, so the window is one request.
- [Transaction rollback leaves an orphan file when the failure is after storage] → explicit delete in the catch block (D7); orphaned files are harmless but avoidable.
- [Deleting Breeze tests removes coverage of login/registration until the test DB lands] → recorded follow-up; manual smoke checks in `tasks.md` cover the flows in the meantime.

## Migration Plan

No schema migration: every column used already exists. Deployment to an existing dev database:

1. `composer install`, `npm install`, `npm run build`.
2. `php artisan migrate:fresh --seed` (the seeded accounts are new; a fresh database avoids stale state from the earlier single seeded user).
3. `php artisan optimize:clear` so route/middleware changes are picked up.
4. Smoke-test: seed logins for all three roles, registration for both roles, blocked/pending sign-in messages, `/admin` as non-admin.

Rollback: revert the branch and re-run `php artisan migrate:fresh --seed`; no schema or production data is involved. `docs/DB.md`'s `diblokir` addition is documentation only and can stay.

## Open Questions

- None blocking. The test database and the admin verification/blocking screens are deliberately deferred to later changes, as recorded in the proposal and D10/D13.
