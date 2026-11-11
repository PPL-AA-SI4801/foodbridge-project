# Design

## Context

The application uses Eloquent models with `#[Fillable]` and `#[WithoutTimestamps]` on most tables, nullable `lat`/`lng` on profiles, and several status enums. Seeders run via `php artisan db:seed` (or `migrate:fresh --seed`). The existing `DatabaseSeeder` creates users with plain `User::create` and plain-text passwords (hashed by the `password` cast). Factories exist only for `User`.

See `proposal.md` - Why.

## Goals / Non-Goals

**Goals:**
- Idempotent seeders that can be re-run without duplicating rows.
- Hand-written arrays for predictable, readable demo data.
- One seeder per entity group, orchestrated by `DatabaseSeeder`.
- Cover every existing feature surface (posts, claims, requests, reviews, notifications, verification documents).
- Document how to add local image assets.

**Non-Goals:**
- Generating fake image binaries or committing large media files.
- Changing any model, migration, controller, or view.
- Replacing the existing `FoodCategorySeeder` categories.

## Decisions

1. **Idempotency via `firstOrCreate`/`updateOrCreate`**
   - Users keyed by `email`; profiles keyed by `user_id`; categories by `nama_kategori`; posts/requests by a stable slug or title.
   - Rationale: avoids duplicate rows on re-run; matches `FoodCategorySeeder` pattern.

2. **Split seeders, one per entity group**
   - `DatabaseSeeder` calls: `FoodCategorySeeder`, `DemoUserSeeder`, `VerificationDocumentSeeder`, `FoodPostSeeder`, `ClaimSeeder`, `FoodRequestSeeder`, `InAppNotificationSeeder`.
   - Rationale: allows `php artisan db:seed --class=ClaimSeeder` for targeted work.

3. **Hand-written arrays, not factories**
   - Rationale: deterministic demo data, stable credentials, and explicit status coverage. Factories remain for tests.

4. **Ordering / dependencies**
   - Categories → users + profiles → verification documents → food posts → claims (with courier details/reviews) → food requests (with donor/claim links) → notifications.
   - Rationale: foreign keys require parents first.

5. **Image strategy**
   - Store a single placeholder file at `storage/app/public/seed/placeholder.webp` (or `public/images/seed-placeholder.webp`) and reference it from `foto`/`foto_bukti`. Document the copy step in a seeder README note.
   - Rationale: avoids committing binaries; keeps views working.

6. **Status coverage**
   - Explicitly seed one record per status for posts, claims, and requests so every UI branch is exercisable.

7. **Daily quota edge case**
   - Seed exactly 3 non-rejected claims on the same day for one recipient to exercise the quota-exhausted branch.

## Risks / Trade-offs

- [Lat/lng nullable] → profiles can omit coordinates; posts require them. Seeder provides fixed Jakarta coordinates.
- [Idempotency keys] → titles must be unique per donor to avoid collisions; use deterministic titles.
- [Image references] → views may `asset()`/`Storage::url()` the path; the placeholder must exist or images 404. Documented.
- [Foreign key ordering] → seeder order enforced in `DatabaseSeeder::run()`.

## Migration Plan

1. Add new seeder classes.
2. Update `DatabaseSeeder::run()` to call them in order.
3. Add placeholder image file + documentation.
4. Run `php artisan migrate:fresh --seed` and verify each feature surface renders.

## Open Questions

- None. All earlier questions resolved (idempotent, hand-written arrays, split files, real image guidance).