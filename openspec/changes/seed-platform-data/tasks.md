# Tasks

## 1. Seeder scaffolding and orchestration

- [x] 1.1 Create split seeder classes (`DemoUserSeeder`, `VerificationDocumentSeeder`, `FoodPostSeeder`, `ClaimSeeder`, `FoodRequestSeeder`, `InAppNotificationSeeder`) under `database/seeders/` and wire them in `DatabaseSeeder::run()` in dependency order; verify `php artisan migrate:fresh --seed` completes without errors.
- [x] 1.2 Make all seeders idempotent using `firstOrCreate`/`updateOrCreate` keyed by stable identifiers; verify re-running `php artisan db:seed` does not duplicate rows.
- [x] 1.3 Add a placeholder image asset and document the copy step; verify seeded `foto`/`foto_bukti` paths resolve or gracefully 404.

## 2. Users and profiles

- [ ] 2.1 Seed 1 admin, 2–3 donors, and 2–3 recipients with profiles and lat/lng, plus 1 pending donor and 1 pending recipient; verify login with the documented demo credentials works for each role.
- [ ] 2.2 Seed verification documents for pending users (nib, akta, etc.) with `menunggu` status, and one approved/rejected document; verify the admin verification queue renders them.

## 3. Food posts and categories

- [ ] 3.1 Seed food posts across all categories and donors with statuses `tersedia`, `habis`, and `kedaluwarsa`; verify the donor dashboard and admin food-post list render counts for each status.

## 4. Claims and reviews

- [ ] 4.1 Seed claims in `pending`, `approved`, `rejected`, and `completed` states, including one recipient with 3 same-day non-rejected claims to exhaust the daily quota; verify the recipient claims page shows the quota-exhausted branch.
- [ ] 4.2 Seed `CourierDetail` for online claims and `FoodReview` for completed claims; verify the claim detail and review UI render without errors.

## 5. Food requests

- [ ] 5.1 Seed food requests in `open`, `in_progress`, `fulfilled`, `expired`, and `cancelled` states with varied urgency and pickup methods, respecting the 3-open-request limit; verify the request board renders all statuses.

## 6. Notifications

- [ ] 6.1 Seed in-app notifications for donor, recipient, and admin using tipe keys recognized by `NotificationPresenter`; verify the notification dropdown and page render icons and unread counts.

## 7. Verification

- [ ] 7.1 Run `php artisan migrate:fresh --seed` and `php artisan test`; verify all feature surfaces render non-empty and no seeder errors occur.