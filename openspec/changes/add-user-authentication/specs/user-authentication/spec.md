# Spec Delta

## Purpose

Gives FoodBridge a single safe entry point to every account: prospective donors and recipients register by submitting a legality document, only approved accounts can sign in, and any signed-in account can end its session or recover its password.

## ADDED Requirements

### Requirement: Donor and recipient self-registration
The system SHALL let a visitor register as either Donor or Penerima from one form, collecting account credentials (`nama`, `email`, `no_hp`, password) and the profile data belonging to the chosen role: for a Donor, `nama_usaha`, `jenis_usaha`, and `alamat`; for a Penerima, `nama_lembaga`, `jenis_lembaga`, and `alamat`. Registration SHALL also require one legality document, described by a document type and an uploaded file. A successful registration SHALL create the account with status `menunggu`, the matching profile, and the document in one atomic operation, and SHALL NOT sign the visitor in.

#### Scenario: Donor registers
- **WHEN** a visitor submits the form with the Donor role, valid account and business fields, and a legality document
- **THEN** the system creates one account with role `donor` and status `menunggu`, one donor profile, and one pending verification document, then shows the sign-in page with a notice that the account awaits verification

#### Scenario: Penerima registers
- **WHEN** a visitor submits the form with the Penerima role, valid account and institution fields, and a legality document
- **THEN** the system creates one account with role `penerima` and status `menunggu`, one recipient profile, and one pending verification document, then shows the sign-in page with a notice that the account awaits verification

#### Scenario: Profile fields follow the chosen role
- **WHEN** the visitor switches the selected role before submitting
- **THEN** the form asks for donor fields for Donor and recipient fields for Penerima, and only the profile matching the chosen role is stored

#### Scenario: Invalid registration input is rejected
- **WHEN** the submitted email is already registered, the business or institution name is already taken, a required field is missing, the password confirmation does not match, or the document is not an accepted type or exceeds the allowed size
- **THEN** the system re-displays the form with field-level errors and creates no account, profile, or document

#### Scenario: Registration is atomic
- **WHEN** storing the account, profile, or document fails partway through an otherwise valid submission
- **THEN** no partial account exists and the visitor can retry

### Requirement: Legality documents are stored privately
Legality documents submitted at registration SHALL be written to private storage, outside the publicly served files, and the application SHALL NOT expose a public URL for them.

#### Scenario: Document is not publicly reachable
- **WHEN** a registration stores a legality document
- **THEN** the file exists in private storage and no public link to it is generated for the registering visitor or any other unauthenticated visitor

### Requirement: Sign-in is limited to active accounts
The system SHALL authenticate an account only when its `status_akun` is `aktif`. An account in `menunggu`, `ditolak`, or `diblokir` SHALL be refused with a message specific to that status, and no session SHALL be created. Incorrect credentials SHALL be refused with a single generic message that does not reveal whether the email exists.

#### Scenario: Active account signs in
- **WHEN** an account with status `aktif` submits the correct email and password
- **THEN** a session is created and the account lands on its dashboard

#### Scenario: Pending account is refused
- **WHEN** the submitted credentials are correct but the account status is `menunggu`
- **THEN** the visitor returns to the sign-in page with a message that the account is waiting for verification, and remains signed out

#### Scenario: Rejected registration is refused
- **WHEN** the submitted credentials are correct but the account status is `ditolak`
- **THEN** the visitor returns to the sign-in page with a message that the registration was rejected, and remains signed out

#### Scenario: Blocked account is refused
- **WHEN** the submitted credentials are correct but the account status is `diblokir`
- **THEN** the visitor returns to the sign-in page with a message that the account is blocked, and remains signed out

#### Scenario: Wrong credentials are refused generically
- **WHEN** the email is unknown or the password is wrong
- **THEN** the visitor returns to the sign-in page with one generic invalid-credentials message, whether or not the email belongs to an account

#### Scenario: Repeated failures are throttled
- **WHEN** sign-in attempts for the same email and client address fail repeatedly within a short period
- **THEN** further attempts are refused for a cooling period instead of being checked against the password

### Requirement: Losing active status ends the session
The system SHALL verify the account status on every authenticated request, so an account that stops being `aktif` loses access immediately rather than at its next sign-in.

#### Scenario: Account blocked while signed in
- **WHEN** a signed-in account's status changes away from `aktif` and that account makes another request
- **THEN** its session ends, it is sent to the sign-in page with the message for its new status, and it cannot reach any authenticated page

### Requirement: Sign-out ends the session
The system SHALL end the authenticated session when the account chooses to sign out, invalidating the session and regenerating its security token.

#### Scenario: Account signs out
- **WHEN** a signed-in account chooses sign out
- **THEN** its session is invalidated, it is sent to the public landing page, and revisiting a protected page requires signing in again

### Requirement: Password reset by email
The system SHALL let an account request a password reset link for its email address and SHALL use that single-use, time-limited link to set a new password, without revealing whether the email is registered.

#### Scenario: Account completes a reset
- **WHEN** an account requests a reset link, opens the emailed link, and submits a new password that satisfies the password rules
- **THEN** the new password signs in successfully and the old password no longer works

#### Scenario: Unknown email is handled without disclosure
- **WHEN** a visitor requests a reset link for an email that is not registered
- **THEN** the page shows the same confirmation as for a registered email and no link is delivered

#### Scenario: Invalid or expired link is refused
- **WHEN** a reset link is malformed, already used, or expired
- **THEN** the new password is not changed and the visitor is told the link is invalid or expired
