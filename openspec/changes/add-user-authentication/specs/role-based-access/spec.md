# Spec Delta

## Purpose

Ensures every signed-in account acts within exactly one role — Donor, Penerima, or Administrator — and that each area of the application is reachable only by the roles allowed to use it.

## ADDED Requirements

### Requirement: Every account has exactly one role
The system SHALL assign every account exactly one role, drawn only from `donor`, `penerima`, and `administrator`, and SHALL treat any other value as invalid. Administrator accounts SHALL be provisioned, never created through self-registration.

#### Scenario: Self-registration offers only donor and penerima
- **WHEN** a visitor registers a new account
- **THEN** only Donor and Penerima can be chosen, and an administrator role cannot be submitted through the registration form

#### Scenario: Every loaded account exposes a known role
- **WHEN** an account is loaded for authentication or access checks
- **THEN** its role is one of the three known values and unauthorized or unknown values never grant access

### Requirement: Guests are sent to sign-in
The system SHALL redirect unauthenticated visitors who request a protected page to the sign-in page, instead of returning an error or exposing the page.

#### Scenario: Guest requests a protected page
- **WHEN** a visitor without a session requests the dashboard or the administrator area
- **THEN** the visitor is redirected to the sign-in page and reaches the requested page only after signing in

### Requirement: Pages are restricted by role
The system SHALL restrict each protected area to an explicit set of roles and SHALL refuse an authenticated account whose role is not allowed, without returning any content from that area.

#### Scenario: Wrong role is forbidden
- **WHEN** an authenticated Donor or Penerima requests the administrator area
- **THEN** the request is refused with a forbidden response and no administrator content is returned

#### Scenario: Allowed role reaches the area
- **WHEN** an authenticated Administrator requests the administrator area
- **THEN** the request succeeds and the administrator page is returned

#### Scenario: Shared page stays open to every active account
- **WHEN** an authenticated Donor, Penerima, or Administrator requests the shared dashboard
- **THEN** the request succeeds regardless of which of the three roles the account holds

### Requirement: The dashboard identifies the signed-in account
The shared dashboard SHALL be available to every active account and SHALL show at least the account's name and role, so a signed-in user can confirm which identity and role is in use.

#### Scenario: Account checks its identity
- **WHEN** a signed-in account opens the dashboard
- **THEN** the page shows the account's name and its role, and offers a way to sign out

### Requirement: An administrator area exists for administrative work
The system SHALL provide an administrator-only area as the home for administrative tasks; account verification and blocking screens arrive in a later change, and the area MAY begin as a placeholder.

#### Scenario: Administrator opens the area
- **WHEN** a signed-in Administrator opens the administrator area
- **THEN** the area is served and is visibly reserved for administrators

#### Scenario: Non-administrator cannot open the area
- **WHEN** a signed-in Donor or Penerima opens the administrator area
- **THEN** access is refused and the account is not redirected to a role-neutral page as if it had succeeded
