# Spec Delta

## Purpose

Allows active Donor and Recipient accounts to review and update their account and organization profile, including a validated location point, so organization information stays accurate and proximity-based discovery can rely on it.

## ADDED Requirements

### Requirement: Profile access is limited to active Donor and Recipient accounts

The system SHALL expose profile viewing and editing only to authenticated accounts whose role is Donor or Recipient and whose account status is active. Accounts that are pending, rejected, or blocked SHALL NOT reach the profile page, and other roles SHALL be forbidden.

#### Scenario: Active donor opens profile
- **WHEN** an authenticated active Donor requests the profile page
- **THEN** the system displays the profile form populated with their account and donor organization data

#### Scenario: Active recipient opens profile
- **WHEN** an authenticated active Recipient requests the profile page
- **THEN** the system displays the profile form populated with their account and recipient organization data

#### Scenario: Inactive account is blocked
- **WHEN** an account that is no longer active requests the profile page
- **THEN** the system ends the session and redirects to sign in with the account status message

#### Scenario: Other role is forbidden
- **WHEN** an authenticated account whose role is not Donor or Recipient requests the profile page
- **THEN** the system returns a forbidden response

### Requirement: Profile form reflects current account and organization data

The system SHALL populate the profile form with the account's current name, email, phone number, organization name, organization type, address, and location point (when present).

#### Scenario: Existing location is shown
- **WHEN** a profile that already has latitude and longitude is opened
- **THEN** the form includes those coordinates and the map centers on them

#### Scenario: Missing location is handled
- **WHEN** a profile has no location point yet
- **THEN** the form is still displayed and the map uses a default center without coordinates being pre-filled

### Requirement: Account details can be updated

The system SHALL allow an account to update its name, email address, and phone number, and SHALL persist the changes.

#### Scenario: Successful account update
- **WHEN** a valid name, email, and phone number are submitted
- **THEN** the system saves them and shows a success confirmation

#### Scenario: Duplicate email is rejected
- **WHEN** the submitted email is already used by another account
- **THEN** the system rejects the update and reports the email conflict

#### Scenario: Invalid email is rejected
- **WHEN** the submitted email is not a valid email address
- **THEN** the system rejects the update and reports the validation error

### Requirement: Password change is optional and verified

The system SHALL allow an optional password change during profile update. When a new password is provided, the system SHALL require the current password to match and the new password to be confirmed; when no new password is provided, the existing password SHALL remain unchanged.

#### Scenario: Successful password change
- **WHEN** the current password is correct and a confirmed new password is provided
- **THEN** the system updates the password and shows a success confirmation

#### Scenario: Wrong current password is rejected
- **WHEN** a new password is provided but the current password does not match
- **THEN** the system rejects the update and reports the current password error

#### Scenario: Unconfirmed new password is rejected
- **WHEN** a new password is provided without a matching confirmation
- **THEN** the system rejects the update and reports the confirmation error

#### Scenario: Empty password leaves it unchanged
- **WHEN** no new password is submitted
- **THEN** the system updates the other fields without changing the password

### Requirement: Donor organization profile can be updated

The system SHALL allow a Donor to update the business name, business type, and address of its organization profile, and SHALL persist the changes.

#### Scenario: Successful donor profile update
- **WHEN** a Donor submits valid business name, business type, and address
- **THEN** the system saves them and shows a success confirmation

#### Scenario: Duplicate business name is rejected
- **WHEN** the submitted business name is already used by another donor profile
- **THEN** the system rejects the update and reports the name conflict

### Requirement: Recipient organization profile can be updated

The system SHALL allow a Recipient to update the institution name, institution type, and address of its organization profile, and SHALL persist the changes.

#### Scenario: Successful recipient profile update
- **WHEN** a Recipient submits valid institution name, institution type, and address
- **THEN** the system saves them and shows a success confirmation

#### Scenario: Duplicate institution name is rejected
- **WHEN** the submitted institution name is already used by another recipient profile
- **THEN** the system rejects the update and reports the name conflict

### Requirement: Location point is validated as a pair

The system SHALL require latitude and longitude to be submitted together. A submission containing only one of the two SHALL be rejected. Latitude SHALL be within -90 to 90 and longitude within -180 to 180. The system SHALL persist the location point when both are valid.

#### Scenario: Valid location is saved
- **WHEN** both a valid latitude and longitude are submitted
- **THEN** the system stores the location point on the profile

#### Scenario: Only latitude is rejected
- **WHEN** a latitude is submitted without a longitude
- **THEN** the system rejects the update and reports the missing coordinate

#### Scenario: Only longitude is rejected
- **WHEN** a longitude is submitted without a latitude
- **THEN** the system rejects the update and reports the missing coordinate

#### Scenario: Out-of-range latitude is rejected
- **WHEN** a latitude outside -90 to 90 is submitted
- **THEN** the system rejects the update and reports the range error

#### Scenario: Out-of-range longitude is rejected
- **WHEN** a longitude outside -180 to 180 is submitted
- **THEN** the system rejects the update and reports the range error

### Requirement: Profile update is atomic

The system SHALL apply account and organization profile changes in a single transaction, so a failure in either part leaves both unchanged.

#### Scenario: Failure rolls back both parts
- **WHEN** an error occurs while saving either the account or the organization profile
- **THEN** neither the account nor the organization profile is changed

### Requirement: Successful update provides confirmation

The system SHALL redirect back to the profile page with a success confirmation after a valid update.

#### Scenario: Success message shown
- **WHEN** a valid profile update is submitted
- **THEN** the system redirects to the profile page and displays a success confirmation