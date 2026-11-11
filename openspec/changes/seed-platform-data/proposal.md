# Proposal

## Why

A fresh `php artisan migrate:fresh --seed` leaves every feature surface empty: no food posts, claims, requests, reviews, notifications, or verification documents. Dashboards, the request board, claim flows, and notification UI all render blank, making manual testing and demos painful. The existing `DatabaseSeeder` only creates one donor, one recipient, one admin, and the six food categories.

## What Changes

- Replace the minimal `DatabaseSeeder` body with a comprehensive, idempotent seed that covers every existing feature.
- Split the seeding logic into focused seeders (`DemoUserSeeder`, `FoodPostSeeder`, `ClaimSeeder`, `FoodRequestSeeder`, `NotificationSeeder`, etc.) orchestrated by `DatabaseSeeder`, so subsets can be run independently.
- Seed users across all roles and account states (aktif, menunggu, ditolak, diblokir), with profiles and verification documents for pending accounts.
- Seed food posts in every status (`tersedia`, `habis`, `kedaluwarsa`) across categories and donors.
- Seed claims in every status (`pending`, `approved`, `rejected`, `completed`), some with courier details and food reviews.
- Seed food requests in every status (`open`, `in_progress`, `fulfilled`, `expired`, `cancelled`) with urgency and pickup-method variety.
- Seed in-app notifications for each role using the tipe keys recognized by `NotificationPresenter`.
- Provide a documented way to add local image assets for `foto`/`foto_bukti` fields without committing large binaries.

## Capabilities

### New Capabilities
- None (tooling only).

### Modified Capabilities
- None (no runtime behavior changes).

## Impact

- Affected files: `database/seeders/*`, `database/factories/*` (optional).
- No migrations, routes, controllers, models, or views change.
- `skip_specs: true` is set because this is tooling, not a runtime capability.
- Out of scope: generating fake image binaries, adding new features, changing business logic.