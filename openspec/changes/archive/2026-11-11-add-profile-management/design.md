# Design

## Context

See `proposal.md` for motivation. The relevant current state:

- Donor and Recipient accounts are created with a matching profile (`donor_profiles` / `recipient_profiles`) and `lat`/`lng` are nullable; registration deliberately omits the location point and the map picker (auth change, D8).
- The profile dropdown (`resources/views/components/header/profile-menu.blade.php`) already links to `profile.edit` when that route exists, so adding the route makes "Profil Saya" appear for everyone; the admin layout uses this component.
- Donor/Recipient authenticated pages still use the Breeze app layout and top navigation (`resources/views/layouts/navigation.blade.php`), which has no profile link.
- `users` has no timestamps and uses enum casts for `role`/`status_akun`; `EnsureAccountIsActive` already ends sessions for non-active accounts.
- No map library is installed; the PRD names Leaflet.js (OpenStreetMap). Only CDN usage is needed for this change.

## Goals / Non-Goals

**Goals:**
- One edit form per role that updates account and organization data atomically.
- A usable, accessible location picker that does not require an API key.
- Role-scoped access that reuses existing middleware.

**Non-Goals:**
- Admin viewing or editing other accounts' profiles (Manajemen Data).
- Profile photo, email verification, or password reset changes.
- Server-side geocoding or storing a searchable address index.
- Adopting the design-system sidebar for Donor/Recipient (still top navigation).

## Decisions

**D1 — Single `ProfileController` resolves the profile by role.**
`edit()` and `update()` inspect `Auth::user()->role`, load `donorProfile` or `recipientProfile` with `firstOrCreate(['user_id' => $user->id])`, and abort 403 for administrators. One controller keeps account+profile handling together. *Alternative rejected:* separate controllers per role — duplicates validation and transaction logic.

**D2 — One `ProfileUpdateRequest` with role-conditional rules.**
Validation switches on the authenticated user's role for organization fields, mirroring `RegisterRequest`'s role switch but driven by the model, not the input, so a crafted request cannot choose the other role's fields. *Alternative rejected:* two form requests — keeps the shared account/location rules duplicated.

**D3 — Update account and profile in one transaction.**
`DB::transaction` updates `users` (name, email, phone, password if provided) and the role's profile (organization fields, address, lat/lng). On any exception nothing is persisted. *Alternative rejected:* sequential saves — a partial failure could leave account and profile out of sync.

**D4 — Optional password change requires the current password.**
`password` is nullable with `confirmed`; `current_password` is `required_with:password` and validated with the `current_password` rule. When `password` is empty, the controller leaves `password` untouched. *Alternative rejected:* always requiring current password — unnecessary friction for non-password edits.

**D5 — Organization name uniqueness ignores the current profile.**
`Rule::unique(...)->ignore($profile->id)` keeps the registration-time uniqueness while allowing a no-op resubmission of the same name. *Alternative rejected:* dropping uniqueness on update — would silently allow collisions with another organization.

**D6 — Location picker uses Leaflet from CDN plus Nominatim.**
The view loads Leaflet CSS/JS from a CDN and uses Nominatim for address search and the browser Geolocation API for "detect my location". The marker is draggable; its coordinates write to hidden `lat`/`lng` inputs. No API key or Composer/npm dependency is added. *Alternatives rejected:* Google Maps (requires API key and billing), manual coordinate inputs only (poor UX and error-prone). *Risk:* Nominatim rate limits (1 req/s) — the search runs only on explicit button press, not on every keystroke.

**D7 — lat/lng are validated as a required pair, not individually required.**
`required_with:lat` / `required_with:lng` plus range checks means a profile can keep a previously stored point without re-entering it, while a partial submission is rejected. Hidden inputs are pre-filled from the stored point, so a normal submission always carries both. *Alternative rejected:* making both globally required — would block an account from saving a phone-number change if the map failed to load; the pair rule still prevents half-filled coordinates.

**D8 — Navigation and profile-menu conditions.**
Donor/Recipient top navigation gains a "Profil Saya" link to `profile.edit` when the route exists. The admin layout's `x-header.profile-menu` condition is tightened to `Route::has('profile.edit') && auth()->user()->hasRole(Role::Donor, Role::Penerima)` so administrators never see a link that 403s. The `Role` enum is referenced in Blade via its fully qualified name. *Alternative rejected:* exposing the route to admins — they have no profile row and would hit 403.

**D9 — No migration and no new disk.**
All columns already exist and are nullable where needed. No storage or environment change.

## Risks / Trade-offs

- [Nominatim availability/CORS] → the picker degrades to the hidden coordinate inputs if search fails; the form still submits manually entered coordinates.
- [Geolocation requires HTTPS/permission] → the button is progressive enhancement; coordinates can still be set by search or by dragging the marker.
- [No automated HTTP tests] → this change, like the previous ones, relies on a manual smoke checklist; the project has no test database.
- [Email change without verification] → accepted, matching the registration flow which also has no email verification.
- [Rejection is effectively terminal for resubmission] → unchanged; profile editing does not reopen a rejected account.

## Migration Plan

No schema migration. Deploy:

1. Add the controller, form request, view, routes, and navigation changes.
2. `php artisan optimize:clear` so the new routes are picked up.
3. Sign in as a donor and recipient seed account, open "Profil Saya", update each field, and verify the location point persists and the map recenters.
4. Verify an administrator does not see the profile link and gets 403 if the URL is requested directly.

Rollback: revert the code. No data or schema rollback is required.

## Open Questions

None blocking. The choice of geocoding provider and whether location becomes globally required can be revisited without changing the spec or task breakdown.