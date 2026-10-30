# Proposal

## Why

Donor and Recipient accounts are created with an organization profile at registration, but `lat`/`lng` are left null and there is no way for an account to correct its business/institution details afterward. FR-02 (profile management including location point) is unimplemented, so organization data can drift out of date and proximity-based discovery (catalog filtering by nearest location) cannot work. The profile dropdown already reserves a `profile.edit` route that does not exist yet, so authenticated Donor/Recipient pages currently have no profile entry point.

## What Changes

- Add a profile page for Donor and Recipient accounts: view and update organization details (business/institution name and type, address), account details (PIC name, email, phone, optional password change), and the location point.
- Add a map-based location picker (Leaflet) with address search (Nominatim), "detect my location" (geolocation), a draggable marker, and hidden latitude/longitude fields bound to the marker.
- Add `profile.edit` (GET) and `profile.update` (PUT) routes behind `auth` and `role:donor,penerima`, plus a `ProfileController` and `ProfileUpdateRequest`.
- Update the donor/recipient navigation dropdown to link "Profil Saya" when the route exists, and tighten the admin profile-menu condition so administrators do not see a route they cannot use.
- Keep `users.status_akun` as the access gate: only `aktif` accounts reach the page (already enforced by `EnsureAccountIsActive`).

## Capabilities

### New Capabilities

- `profile-management`: Donor/Recipient viewing and updating of account and organization profile data, including a validated location point, with role-scoped access.

### Modified Capabilities

(none — no existing spec covers profile management; `user-authentication` and `role-based-access` remain valid)

## Impact

- New: `app/Http/Controllers/ProfileController.php`, `app/Http/Requests/ProfileUpdateRequest.php`, `resources/views/profile/edit.blade.php`.
- Modified: `routes/web.php`, `resources/views/layouts/navigation.blade.php`, `resources/views/components/header/profile-menu.blade.php`.
- Frontend: Leaflet (CDN) and Nominatim client-side search added to the profile view; no new Composer or npm dependency.
- Data: no migration; uses existing `users`, `donor_profiles`, `recipient_profiles` columns, including nullable `lat`/`lng`.
- Validation: organization name uniqueness is re-checked while ignoring the current profile; password change requires the current password.