# Tasks

## 1. Routes and controller

- [ ] 1.1 Add `profile.edit` (GET) and `profile.update` (PUT) routes in `routes/web.php` inside the `auth` group with `role:donor,penerima` middleware, and verify `php artisan route:list --name=profile` shows both
- [ ] 1.2 Create `app/Http/Controllers/ProfileController.php` with `edit()` resolving the role's profile via `firstOrCreate` and returning `profile.edit`, and verify an active donor/recipient can open the page
- [ ] 1.3 Implement `update()` to persist account and profile fields inside a transaction and redirect back with a success status, and verify a valid submission updates both tables

## 2. Validation

- [ ] 2.1 Create `app/Http/Requests/ProfileUpdateRequest.php` with account rules (name, unique email ignoring self, phone, optional password with current_password) and verify invalid inputs are rejected with Indonesian messages
- [ ] 2.2 Add role-conditional organization rules for donor (`nama_usaha`, `jenis_usaha`) and recipient (`nama_lembaga`, `jenis_lembaga`) plus shared `alamat`, and verify the wrong role's fields are ignored
- [ ] 2.3 Add paired latitude/longitude validation with range checks, and verify partial or out-of-range coordinates are rejected

## 3. Profile view and map picker

- [ ] 3.1 Create `resources/views/profile/edit.blade.php` using the app layout with account, organization, address, and hidden lat/lng fields, and verify it renders for an active donor/recipient
- [ ] 3.2 Add Leaflet (CDN) map with a draggable marker bound to hidden lat/lng inputs and default center when no coordinates exist, and verify moving the marker updates the hidden inputs
- [ ] 3.3 Add address search via Nominatim that moves the marker and updates coordinates, plus a geolocation button, and verify each sets the marker and coordinates

## 4. Navigation and access

- [ ] 4.1 Add a "Profil Saya" link to `resources/views/layouts/navigation.blade.php` pointing to `profile.edit` when the route exists, and verify it appears for donor/recipient
- [ ] 4.2 Tighten `resources/views/components/header/profile-menu.blade.php` so the profile link only shows for Donor/Recipient roles, and verify administrators do not see it
- [ ] 4.3 Verify direct requests to `profile.edit` as an administrator return 403

## 5. Integration checks

- [ ] 5.1 Smoke-test the full flow for donor and recipient seed accounts: open profile, edit account/org/address/location, submit, confirm persistence and success message, and confirm the map recenters on reload
- [ ] 5.2 Smoke-test rejected scenarios: duplicate email, duplicate organization name, wrong current password, unconfirmed password, partial coordinates, and out-of-range coordinates each show the expected validation error
- [ ] 5.3 Run `php artisan optimize:clear` and `php artisan route:list` to confirm routes and middleware are registered, and confirm `php artisan test` still passes