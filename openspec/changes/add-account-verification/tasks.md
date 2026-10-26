# Tasks

## 1. Status vocabulary and sign-in cleanup

- [x] 1.1 Add `App\Enums\VerificationStatus` with `menunggu`, `disetujui`, and `ditolak`, cast it on `VerificationDocument`, and use it in registration instead of the raw `'menunggu'` string; verify the enum resolves and registration still creates a pending document during the group 3 smoke test.
- [x] 1.2 Translate `AccountStatus::refusalMessage()` to Indonesian for `menunggu`, `ditolak`, and `diblokir`, and extend `tests/Unit/EnumsTest.php` to assert the exact messages; verify with `php artisan test --filter=Enums`.
- [x] 1.3 Change the account-status error from the `email` key to a dedicated `account_status` key in both `LoginRequest::ensureAccountIsActive()` and `EnsureAccountIsActive`; verify `grep -rn "account_status" app/Http/Requests/Auth/LoginRequest.php app/Http/Middleware/EnsureAccountIsActive.php` shows both paths and that email-field errors are no longer used for account status.
- [x] 1.4 Update `resources/views/auth/login.blade.php`: render `account_status` as a dedicated alert above the form with `role="alert"`, translate the visible labels to Indonesian, and remove the Remember me block; verify `grep -n "Remember me\|remember_me" resources/views/auth/login.blade.php` returns nothing and the page renders the alert in the group 3 smoke test.
- [x] 1.5 Translate the wrong-credentials and throttle messages in `LoginRequest` and the registration success flash in `RegisteredUserController` to Indonesian; verify a wrong password shows the Indonesian generic message and a completed registration shows the Indonesian waiting notice in the group 3 smoke test.
- [x] 1.6 Remove the `remember_token` write and the now-unused `Str` import from `NewPasswordController`; verify `grep -rn "remember_token" app resources/views` returns no application writes and complete a password reset through the log mailer in group 5.
- [x] 1.7 Extend `tests/Unit/EnumsTest.php` with the `VerificationStatus` values and update the existing account-status assertions; verify `php artisan test --filter=Enums` passes without a database.

## 2. Verification domain, routes, and actions

- [x] 2.1 Add `User::scopePendingVerification()` (Donor/Penerima plus `menunggu`) and a `profileName()` helper for `nama_usaha`/`nama_lembaga`; verify `php artisan tinker --execute="echo App\Models\User::query()->pendingVerification()->count();"` matches the pending donor/penerima rows.
- [x] 2.2 Add `VerificationDocument::jenisDokumenLabel()` for `nib`, `akta_pendirian`, `izin_operasional`, and `lainnya`, with a unit test; verify with `php artisan test --filter=VerificationDocument`.
- [x] 2.3 Add the administrator routes for the queue, review detail, approve, reject, and private document stream under the existing `role:administrator` group; verify the `admin.verifications.*` and `admin.documents.show` route names are registered.
- [x] 2.4 Add `Admin\RejectVerificationRequest` with a required `catatan` (max 255) and Indonesian attribute/messages; verify the rejection form reports the Indonesian required message when submitted without a reason in the group 3 smoke test.
- [x] 2.5 Add `Admin\VerificationController@index` and `@show`: pending Donor/Penerima only, eager-loaded profiles and documents, ordered by newest document id, with a read-only detail for accounts that are no longer pending; verify the queue and detail render as the administrator and the count matches the query in group 3.
- [x] 2.6 Add `Admin\VerificationController@approve`: transaction, re-check role and `menunggu` status, require at least one document, set the account to `aktif`, mark pending documents `disetujui` with `verified_at`, clear `catatan`, and flash an Indonesian success message; verify an approved account signs in and the document row shows the review outcome in group 3.
- [x] 2.7 Add `Admin\VerificationController@reject`: transaction, the same guards, set the account to `ditolak`, mark pending documents `ditolak` with `verified_at` and `catatan`, and flash an Indonesian success message; verify the rejected applicant is refused at sign-in and the reason is stored in group 3.
- [x] 2.8 Add `Admin\VerificationDocumentController@show`: 404 when the private file is missing, otherwise stream it inline with a friendly filename, `Cache-Control: no-store, private`, and `X-Content-Type-Options: nosniff`; verify an administrator opens the document, a non-admin gets 403, and a guest is redirected in group 5.
- [x] 2.9 Verify the administrator route registration with `php artisan route:list --name=admin.verifications` and `php artisan route:list --name=admin.documents` after the controllers exist; verify both commands list every route.

## 3. Administrator verification UI and end-to-end flow

- [ ] 3.1 Add `resources/views/components/status-badge.blade.php` using the status map in `docs/design_system.md`; verify the queue and detail render `menunggu`, `disetujui`, `ditolak`, and `aktif` with distinct styles and `npm run build` succeeds.
- [ ] 3.2 Add `resources/views/admin/verifications/index.blade.php` using `x-admin-layout`: applicant name, role, business/institution, document type, and a review link, with an empty state; verify an administrator sees pending accounts and the empty state when none remain.
- [ ] 3.3 Add `resources/views/admin/verifications/show.blade.php`: identity/profile card, document list with “Lihat Dokumen”, approve button, and reject form with the required reason; verify the document opens in a new tab, a processed account is read-only, and an account with no document cannot be approved or rejected.
- [ ] 3.4 Update `resources/views/components/admin-layout.blade.php` to render `session('status')` and `session('error')` alerts and rename the header label to “Menunggu Verifikasi”; verify success and guard-failure messages appear after an administrator action.
- [ ] 3.5 Update `Admin\DashboardController` to use `pendingVerification()` and update `resources/views/admin/dashboard.blade.php` with a link to the queue and the same count; verify the dashboard, header, and sidebar badge show the same number.
- [ ] 3.6 Smoke-test approval: register a Donor, sign in as the administrator, confirm the applicant appears in the queue, open the document, approve, then sign in as the applicant and reach `/dashboard`; verify the document is recorded `disetujui` with `verified_at` and the account is `aktif`.
- [ ] 3.7 Smoke-test rejection: register a Penerima, reject without a reason and confirm the Indonesian validation error, reject with a reason and confirm the account is `ditolak`, then attempt to sign in and confirm the dedicated Indonesian rejection alert and no session.
- [ ] 3.8 Smoke-test the waiting message and Remember me removal: attempt to sign in as a pending account and confirm the dedicated Indonesian waiting alert, then confirm the login page has no Remember me control and still signs in an active account.

## 4. Documentation

- [ ] 4.1 Update `docs/DB.md` to state that account status is the access source of truth, document status records the review outcome, and approval/rejection updates both together; verify the documented flow matches the implemented actions and the existing `docs/DB.md` status vocabulary.
- [ ] 4.2 Update `docs/FIRST_SETUP.md`: replace the manual `php artisan tinker` activation workaround with the administrator verification flow, describe approve/reject and the required reason, and keep a note that `diblokir` remains supported by the gate but blocking is a later feature; verify the documented steps work as written.
- [ ] 4.3 Remove stale “admin approval screens are a later change” wording from `docs/FIRST_SETUP.md`; verify `grep -rn -i "later change\|approval screens" docs/FIRST_SETUP.md` returns nothing that contradicts the delivered flow.

## 5. Integration verification

- [ ] 5.1 Run `php artisan optimize:clear`, `php artisan route:list`, `vendor/bin/pint --dirty`, `php artisan test`, and `npm run build`; verify all commands succeed and the route list contains the verification and document routes.
- [ ] 5.2 Verify access control: a guest is redirected to sign-in from the queue, detail, actions, and document routes; a signed-in Donor and Penerima receive 403 from each; an administrator reaches each.
- [ ] 5.3 Verify document privacy: the uploaded file is absent from `public/`, the document URL only resolves through the administrator route, and a missing file returns 404.
- [ ] 5.4 Verify double decisions: approve an account twice and confirm the second attempt is refused with the “already processed” notice while the account status and document outcome stay unchanged.
- [ ] 5.5 Verify password recovery end to end through the log mailer and confirm no `remember_token` error occurs; verify a rejected account still cannot sign in and an approved account can.
- [ ] 5.6 Verify `git status` contains no uploaded legality documents, generated build output, or unrelated changes before handing the change over.
