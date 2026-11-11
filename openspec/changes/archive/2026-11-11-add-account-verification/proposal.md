# Proposal

## Why

Registration already creates a Donor or Penerima account with `status_akun = menunggu` and stores one legality document privately, but there is no way for an administrator to review that document or approve the account. The only current workaround is a documented `php artisan tinker` command, so the registration flow dead-ends and the platform cannot enforce that only legally recognized organizations use it.

## What Changes

- Add an administrator verification queue for pending Donor and Penerima registrations, showing the applicant's identity, role, profile, and submitted document.
- Add a review detail page with the legal document, an **approve** action, and a **reject** action that requires a reason.
- Stream legal documents through an administrator-only route from the existing private `local` disk; no public URL is introduced.
- Make `users.status_akun` the single source of truth for access. Approval sets it to `aktif`; rejection sets it to `ditolak`. `verification_documents.status` records the review outcome (`disetujui` / `ditolak`) and is never consulted for access.
- Update both statuses in one transaction when the administrator decides, plus `verification_documents.verified_at` and, for rejection, `verification_documents.catatan`.
- Show the waiting and rejected account status as a dedicated Indonesian alert on the sign-in page instead of as an email-field error.
- Remove the remaining Remember me control and fix the password-reset path that still writes a non-existent `users.remember_token` column.
- Use Indonesian for all new and touched user-facing text.
- Update `docs/DB.md` and `docs/FIRST_SETUP.md` so the administrator flow replaces the manual activation workaround.
- **Out of scope:** blocking or unblocking active accounts, active-account status management, user CRUD, resubmission after rejection, reviewer identity/audit history, notifications, search/filter/pagination, bulk actions, and a test database.

## Capabilities

### New Capabilities

- `account-verification`: an administrator-only queue for pending Donor and Penerima registrations, private legal-document review, account-level approval or rejection with a required reason, and the status synchronization that lets approved accounts sign in.

### Modified Capabilities

(none — the existing `user-authentication` and `role-based-access` requirements remain valid; this change adds the administrator workflow those specs anticipated)

## Impact

- New: `app/Enums/VerificationStatus.php`, `app/Http/Controllers/Admin/VerificationController.php`, `app/Http/Controllers/Admin/VerificationDocumentController.php`, `app/Http/Requests/Admin/RejectVerificationRequest.php`, administrator verification views, and a shared status-badge component.
- Modified: `routes/web.php`, `app/Models/User.php`, `app/Models/VerificationDocument.php`, `app/Enums/AccountStatus.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Middleware/EnsureAccountIsActive.php`, `app/Http/Controllers/Auth/NewPasswordController.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, the login view, and the administrator dashboard/layout.
- Documentation: `docs/DB.md` (status ownership and review flow) and `docs/FIRST_SETUP.md` (administrator approval replaces manual activation).
- Data: no migration and no new column. Approval/rejection uses the existing `status_akun`, `verification_documents.status`, `catatan`, and `verified_at` columns.
- Constraints: a single administrator is assumed, so no row locking is added; the decision still re-checks the account status inside the transaction to prevent accidental double processing.
