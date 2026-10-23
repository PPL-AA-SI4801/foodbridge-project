# Tasks

## 1. Scaffold the authentication stack and restore the frontend toolchain

- [ ] 1.1 Add `laravel/breeze` as a dev dependency and verify it with `composer show laravel/breeze`.
- [ ] 1.2 Run `php artisan breeze:install blade --no-interaction` and verify the expected files exist (`app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `resources/views/auth/login.blade.php`, `routes/auth.php`).
- [ ] 1.3 Restore the Tailwind 4 toolchain: `git checkout -- vite.config.js resources/css/app.css resources/js/app.js`, delete `tailwind.config.js` and `postcss.config.js`, remove `tailwindcss@^3`/`postcss`/`autoprefixer` from `package.json`, keep `alpinejs` and `@tailwindcss/forms` (loaded via `@plugin '@tailwindcss/forms';` in `resources/css/app.css`), then verify `npm install && npm run build` succeeds and `resources/css/app.css` still uses the Tailwind 4 import.
- [ ] 1.4 Configure `->redirectGuestsTo('/login')` in `bootstrap/app.php` and verify a guest request to `/dashboard` redirects to `/login` instead of returning a bare 401.
- [ ] 1.5 Delete Breeze's database-backed feature tests (`tests/Feature/Auth/*`, `tests/Feature/ProfileTest.php`) and verify `php artisan test` passes with the remaining suite.

## 2. Role and account-status vocabulary

- [ ] 2.1 Add `App\Enums\Role` (`donor`, `penerima`, `administrator`) and `App\Enums\AccountStatus` (`menunggu`, `aktif`, `ditolak`, `diblokir`), cast both in `User::casts()`, and add role/status helpers used by the gate (`isActive()`, `hasRole()`); verify the casts resolve with `php artisan tinker` (`User::first()->role` when a row exists, or new `User(['role' => 'donor'])->role`).
- [ ] 2.2 Add `diblokir` as the fourth `users.status_akun` value in `docs/DB.md` §5 with its meaning ("blocked after approval, distinct from a rejected registration") and verify the documented values match `AccountStatus` exactly.
- [ ] 2.3 Add a database-free unit test for both enums' values and their `User` casts; verify it passes with `php artisan test --filter=Enum`.

## 3. Sign-in, sign-out, password reset, and demo accounts

- [ ] 3.1 Trim Breeze's `routes/auth.php` and views to sign-in, sign-out, forgot-password, and reset-password: remove email verification and password-confirmation routes, controllers, requests, and views; verify `php artisan route:list` lists no `verification.*`, `password.confirm`, or `profile.*` route.
- [ ] 3.2 Adapt `LoginRequest` (drop remember-me, keep the email+IP throttle) and `AuthenticatedSessionController` so sign-in attempts credentials, refuses any status other than `aktif` with a status-specific message, regenerates the session on success, and invalidates it on sign-out; verify by signing in as an `aktif` account and confirming the dashboard loads and the session ID changes.
- [ ] 3.3 Replace the database seeder with one `aktif` account per role (`donor@foodbridge.test`, `penerima@foodbridge.test`, `admin@foodbridge.test`, password `password`) including the matching profile, and update `UserFactory` to fill `nama`, `role`, and `status_akun`; verify `php artisan migrate:fresh --seed` creates exactly those accounts with profiles and `php artisan test` stays green.
- [ ] 3.4 Document the seed accounts and sign-in behavior in `docs/FIRST_SETUP.md`; verify the documented credentials work as written.
- [ ] 3.5 Manually verify the status gate: with `php artisan tinker`, set an account to `menunggu`, `ditolak`, and `diblokir` in turn and confirm each sign-in attempt is refused with its own message and no session is created.

## 4. Donor and recipient registration with legality document

- [ ] 4.1 Add `RegisterRequest` with shared rules (unique email, `no_hp`, password confirmation), role-specific profile rules (unique `nama_usaha` / `nama_lembaga`, `jenis_usaha` / `jenis_lembaga`, `alamat`), `role` limited to `donor`/`penerima`, and a required document (`pdf`, `jpg`, `jpeg`, `png`, max 2 MB); verify invalid payloads return field errors and create no rows.
- [ ] 4.2 Rewrite `RegisteredUserController@store` to store the document on the private `local` disk, create account + profile + pending document inside `DB::transaction`, delete the stored file if the transaction fails, and redirect to `/login` with a pending-verification notice instead of signing in; verify one donor registration writes one row in each of `users`, `donor_profiles`, and `verification_documents`, and that the file exists under `storage/app/private/verification-documents/{user_id}/` only.
- [ ] 4.3 Rewrite `resources/views/auth/register.blade.php` with a role selector that toggles donor/recipient field groups and includes the document type and file inputs; verify both roles render only their own fields and the success notice appears after submit.
- [ ] 4.4 Verify rejection paths end to end: duplicate email, duplicate business/institution name, mismatched password confirmation, oversized file, and wrong file type each re-display the form with errors and leave no account, profile, document, or file behind.
- [ ] 4.5 Document registration in `docs/FIRST_SETUP.md`: that new accounts start as `menunggu`, cannot sign in until an administrator approves them, how to activate one manually for now (`php artisan tinker`), and the 2 MB upload ceiling plus the `upload_max_filesize`/`post_max_size` note; verify the documented steps work as written.

## 5. Dashboard, roles, and the administrator area

- [ ] 5.1 Add `EnsureUserHasRole` (variadic roles, `abort(403)` on mismatch) and register it as the `role` middleware alias in `bootstrap/app.php`; verify a request with a mismatched role returns 403.
- [ ] 5.2 Rewrite `routes/web.php` (welcome, `require routes/auth.php`, authenticated `/dashboard`, admin area) and replace Breeze's dashboard view with one that shows the signed-in account's `nama`, role, and a sign-out action; verify each seed role lands on it after sign-in.
- [ ] 5.3 Add the administrator area route, controller, and placeholder view protected by `role:administrator`; verify an administrator gets the page, a donor or penerima gets 403, and a guest is redirected to sign-in.
- [ ] 5.4 Delete Breeze's profile screens (controller, requests, views, password-update routes) and trim the navigation to Dashboard, Admin (administrators only), and Logout using `nama`; verify with `grep -rn "profile.edit\|->name\b" resources/views app/Http` that no dead references remain and every auth page renders.

## 6. Integration verification and delivery

- [ ] 6.1 Run `php artisan migrate:fresh --seed`, `npm run build`, and `php artisan optimize:clear`, then sign in as each seed role and confirm the dashboard shows the right name and role.
- [ ] 6.2 Exercise the full story in a browser: register a donor and a penerima, confirm both are refused sign-in as `menunggu`, activate one with `php artisan tinker`, confirm it can sign in, then flip it to `diblokir` mid-session and confirm the next request ends the session.
- [ ] 6.3 Verify `php artisan test` passes, `php artisan route:list` resolves without errors, and `git status` contains no uploaded documents or build output.
- [ ] 6.4 Commit the change as the planned conventional-commit series and update PR #5's title and description to describe authentication and access control; verify `git log --oneline` and the PR body match the delivered scope.
