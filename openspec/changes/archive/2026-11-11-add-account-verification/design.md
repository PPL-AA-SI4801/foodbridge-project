# Design

## Context

See `proposal.md` for motivation. The relevant current state:

- Registration already creates a Donor or Penerima account with access status `menunggu`, the matching profile, and one `verification_documents` row with review status `menunggu`, stored privately on the `local` disk under `storage/app/private`.
- Sign-in and every authenticated request already gate access on `users.status_akun`. `AccountStatus` has `menunggu`, `aktif`, `ditolak`, and `diblokir`; `EnsureAccountIsActive` ends a session whose account is no longer active.
- The administrator area exists behind `role:administrator`. The sidebar already links to `admin.verifications.index` and falls back to `#` while the route is missing.
- `users` and `verification_documents` have no timestamps. `verification_documents` already has `status`, nullable `catatan`, and nullable `verified_at`.
- The verification pages arrive after the unarchived `add-user-authentication` change; this design assumes that code is present.
- The test suite has no database coverage: `phpunit.xml` targets SQLite, but `pdo_sqlite` is not installed.

## Goals / Non-Goals

**Goals:**

- Give the administrator enough information to make a legality decision without leaving the application.
- Keep exactly one authority for access and update the review record with it atomically.
- Keep legality documents private and reachable only through an authorized administrator route.
- Make the verification outcome legible to the applicant in Indonesian at sign-in.
- Remove the dead Remember me path, including the password-reset write that targets a column the schema does not have.

**Non-Goals:**

- Blocking or unblocking accounts, account status management for active accounts, and user CRUD.
- Resubmission after rejection.
- Reviewer identity, a status history table, or timestamps for the queue.
- Notifications, email, search, filters, pagination, or bulk decisions.
- A test database or a broad translation of the existing auth pages.

## Decisions

**D1 — Account status is the only access gate; document status is a review record.**
`users.status_akun` already drives `LoginRequest::ensureAccountIsActive()` and `EnsureAccountIsActive`. The document's review status must not be read by either. This avoids two competing authorities and keeps the existing middleware unchanged. *Alternative rejected:* derive account access from document status — it would require rewriting the gate and would leave no record of an account-level decision.

**D2 — The administrator decides the account, not each document.**
One decision approves or rejects the organization. All pending documents receive the decision's outcome. Registration currently produces exactly one document, so per-document judgment adds UI and state without changing the outcome. *Alternative rejected:* per-document approval — only useful if an organization can submit several independently judged documents, which this flow does not produce.

**D3 — Use the existing columns; no migration.**
Approval writes `status_akun = aktif` and `verification_documents.status = disetujui`. Rejection writes `status_akun = ditolak` and `verification_documents.status = ditolak` plus `catatan` and `verified_at`. Both writes happen in one `DB::transaction`, and the controller re-checks `status_akun = menunggu` inside it. *Alternative rejected:* adding a reviewer column, status history table, or timestamps — useful, but outside this story and not required to make the decision safely.

**D4 — Explicit decision routes, not a free status selector.**
Only `Setujui` (`menunggu -> aktif`) and `Tolak` (`menunggu -> ditolak`) are exposed. A selector would permit nonsensical transitions such as `menunggu -> diblokir` or `ditolak -> aktif` and could lock out an active account. Explicit routes keep the transition set reviewable and testable. *Alternative rejected:* a generic status update endpoint — too permissive for this story.

**D5 — Serve documents through an administrator-only controller, not a public URL.**
The route lives inside the existing `role:administrator` group and streams from the private `local` disk. It returns a friendly filename, `Content-Disposition: inline`, `Cache-Control: no-store, private`, and `X-Content-Type-Options: nosniff`. Missing files return 404. This follows the authentication change's decision that legality documents must never be public. *Alternative rejected:* `storage:link` or copying files to `public/` — exposes sensitive documents.

**D6 — One administrator is assumed; concurrency locking is deferred.**
No `lockForUpdate` is added. The decision still runs in a transaction and re-checks the waiting status, so a double submit or a stale page cannot silently approve an already-processed account; it receives a "already processed" notice instead. A second administrator would require a real concurrency design later.

**D7 — Model document review status as an enum.**
Add `VerificationStatus` (`menunggu`, `disetujui`, `ditolak`) and cast it on `VerificationDocument`. Registration and the administrator actions use the enum instead of raw strings. `AccountStatus` remains the account-level enum and gains Indonesian refusal messages.

**D8 — Present account status as a dedicated sign-in alert.**
Both `LoginRequest::ensureAccountIsActive()` and `EnsureAccountIsActive` report the status under a dedicated `account_status` error key instead of the `email` key. The login view renders that key as an alert above the form, separate from credential errors. This makes the waiting/rejected status visible without conflating it with an invalid email address.

**D9 — Remove Remember me completely.**
Delete the checkbox from the login view. `LoginRequest` already ignores the field. `NewPasswordController` still writes `remember_token`, a column `users` does not have; remove that write and the unused `Str` import. *Alternative rejected:* adding the column — the ERD deliberately omits it and remember-me is not a product requirement.

**D10 — Queue ordering uses the newest document id, not a timestamp.**
Without timestamps there is no honest "waiting since" value. The queue orders by `verification_documents.id` descending and does not display a submission time. Adding timestamps is a separate schema change.

**D11 — Build on the existing administrator shell and design system.**
Use `x-admin-layout`, the existing sidebar, the design-system status colors, and a new `x-status-badge` component. The pending count is supplied by the verification and dashboard controllers so the header and sidebar badge agree. The administrator detail page is read-only for accounts that are no longer pending, which prevents a stale back-navigation from showing active actions.

**D12 — Unit coverage only, plus a manual smoke checklist.**
Enums and messages are unit-testable. Feature tests need a database; the project has no test database and no `pdo_sqlite`. This change does not add one. The manual checklist in `tasks.md` covers the HTTP and database paths.

## Risks / Trade-offs

- **No timestamps** → the queue cannot show how long an applicant has waited; it orders by document id and the limitation is documented.
- **No reviewer identity or history** → with one administrator the reviewer is implicit and the current document note is the only record; a second administrator requires an audit design.
- **No row locking** → double submits are stopped by the status re-check, but simultaneous administrators are not handled; revisit when the team grows.
- **Rejection is effectively terminal** → the applicant cannot sign in to resubmit, and the unique business/institution name blocks a fresh registration. This is a product limitation to record; resubmission is a follow-up change.
- **Private document streaming** → a missing `role:administrator` guard or a public symlink would leak sensitive files; keep the route inside the administrator group and verify with a non-admin smoke test.
- **Changing the login error key** → `LoginRequest` and `EnsureAccountIsActive` must both switch to `account_status`, or one path still labels the status as an email error; smoke-test pending and rejected sign-ins.
- **Password-reset regression** → removing the `remember_token` write must leave the reset flow working; verify it with the log mailer.
- **Translation scope** → only the touched login/status text and the new administrator pages are Indonesian; the rest of the Breeze auth pages remain English until a separate translation pass.
- **No automated HTTP tests** → regressions in the decision transaction or document authorization rely on the manual checklist; adding a test database remains a follow-up.

## Migration Plan

No schema migration and no data backfill. Existing pending accounts can be processed immediately; existing active seed accounts are unaffected.

1. Deploy the code and views.
2. Run `php artisan optimize:clear` so the new routes and middleware registration are picked up.
3. Run `npm run build` for the new Tailwind classes.
4. Sign in as the administrator seed account, process an existing pending registration, and verify the applicant's next sign-in.
5. Update the documented activation instructions; the `php artisan tinker` workaround is no longer needed.

Rollback: revert the code and rebuild assets. Accounts already approved or rejected keep their stored status; no schema rollback is required.

## Open Questions

None blocking. Concurrency, reviewer identity, timestamps, resubmission, and account blocking are deliberately deferred.
