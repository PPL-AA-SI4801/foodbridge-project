# Spec Delta

## Purpose

Lets an administrator review the legality documents of pending Donor and Penerima registrations and decide whether the account may use the platform.

## ADDED Requirements

### Requirement: Pending registrations are listed for review

The system SHALL provide an administrator-only queue of Donor and Penerima accounts whose registration is awaiting verification. For each applicant the queue SHALL show the account name, role, business or institution name, contact identity, and the submitted legality document. Administrator accounts and accounts that are no longer awaiting verification SHALL NOT appear in the queue.

#### Scenario: Pending applicant appears in the queue

- **WHEN** an administrator opens the verification queue
- **THEN** every Donor and Penerima account awaiting verification is listed with its identity, role, profile name, and submitted document

#### Scenario: Processed and administrator accounts are absent

- **WHEN** an account is already active or rejected, or the account is an administrator
- **THEN** it does not appear in the pending verification queue

### Requirement: Administrators can inspect the legality document

The system SHALL let an administrator open the legality document submitted with a pending registration. The document SHALL be served from private storage through an authorized application route and SHALL NOT be reachable through a public URL or by a non-administrator.

#### Scenario: Administrator opens the document

- **WHEN** an administrator opens the document from a pending registration
- **THEN** the document is displayed without exposing its private storage location

#### Scenario: Non-administrator cannot open the document

- **WHEN** a guest or an authenticated Donor or Penerima requests the document
- **THEN** the request is refused and no document content is returned

### Requirement: Approval activates the account

The system SHALL let an administrator approve a pending Donor or Penerima registration only when the account has at least one submitted legality document. Approval SHALL mark the account active, record the pending document or documents as approved with the review time, and allow the applicant to sign in.

#### Scenario: Administrator approves a pending registration

- **WHEN** an administrator approves a pending account that has a legality document
- **THEN** the account becomes active, its pending document is recorded as approved with the review time, and the applicant can sign in

#### Scenario: Approval without a document is refused

- **WHEN** an administrator tries to approve a pending account that has no legality document
- **THEN** the account remains awaiting verification and the administrator is told that a document is required

#### Scenario: Applicant signs in after approval

- **WHEN** an approved applicant submits the correct credentials
- **THEN** a session is created and the applicant reaches the dashboard

### Requirement: Rejection records a reason and denies access

The system SHALL let an administrator reject a pending Donor or Penerima registration with a required reason. Rejection SHALL mark the account rejected, record the reason and review time on the pending document or documents, and prevent the applicant from signing in.

#### Scenario: Administrator rejects with a reason

- **WHEN** an administrator rejects a pending registration and supplies a reason
- **THEN** the account becomes rejected, the reason and review time are recorded on the document, and the applicant cannot sign in

#### Scenario: Rejection without a reason is refused

- **WHEN** an administrator tries to reject a registration without supplying a reason
- **THEN** the decision is not applied, the account remains awaiting verification, and the form reports that a reason is required

#### Scenario: Rejected applicant is refused at sign-in

- **WHEN** a rejected applicant submits the correct credentials
- **THEN** no session is created and the sign-in page shows the rejection status

### Requirement: Account status alone controls access

The system SHALL decide whether an account may use the platform solely from the account's access status. The review status of a legality document SHALL record the administrator's decision and SHALL NOT independently grant or deny access. An approval or rejection SHALL update the account status and the document review outcome together, so a decision cannot leave only one of them changed.

#### Scenario: Approved account uses the platform

- **WHEN** an account's access status is active and its document is recorded as approved
- **THEN** the account may sign in and use the platform

#### Scenario: Rejected or waiting account is denied

- **WHEN** an account's access status is awaiting verification or rejected, even if a document row exists
- **THEN** the account cannot sign in and is shown the message for that status

#### Scenario: Decision is all-or-nothing

- **WHEN** an administrator approves or rejects a pending registration
- **THEN** the account status and every pending document review outcome are updated together, or none of them are updated

### Requirement: A registration is decided once

The system SHALL accept a verification decision only for a Donor or Penerima account that is still awaiting verification. Once an account has been approved or rejected, later decisions SHALL be refused without changing the existing status or document outcome.

#### Scenario: Second decision is refused

- **WHEN** an administrator submits a decision for an account that has already been approved or rejected
- **THEN** the existing status and document outcome remain unchanged and the administrator is told the registration was already processed

### Requirement: Verification work is restricted to administrators

The system SHALL make the verification queue, the review detail, the decision actions, and the legality documents available only to administrators. Guests SHALL be sent to sign in; authenticated Donors and Penerimas SHALL be refused without receiving verification content.

#### Scenario: Administrator uses the verification area

- **WHEN** an authenticated administrator requests the queue, a review detail, a decision action, or a document
- **THEN** the request succeeds

#### Scenario: Donor or Penerima is refused

- **WHEN** an authenticated Donor or Penerima requests any verification page, decision action, or document
- **THEN** the request is refused and no verification content is returned

#### Scenario: Guest is sent to sign in

- **WHEN** a guest requests any verification page or document
- **THEN** the guest is redirected to the sign-in page

### Requirement: Applicants see the verification outcome at sign-in

After correct credentials, the sign-in page SHALL present the account's waiting or rejected status as a dedicated message in Indonesian. The status SHALL NOT be revealed when the credentials are incorrect, and no session SHALL be created for an account that is not active.

#### Scenario: Waiting applicant sees the waiting message

- **WHEN** an account awaiting verification submits the correct credentials
- **THEN** the sign-in page shows a dedicated Indonesian message that the account is waiting for administrator verification, and no session is created

#### Scenario: Rejected applicant sees the rejection message

- **WHEN** a rejected account submits the correct credentials
- **THEN** the sign-in page shows a dedicated Indonesian message that the registration was rejected, and no session is created

#### Scenario: Wrong credentials do not reveal status

- **WHEN** someone submits an unknown email or a wrong password
- **THEN** the sign-in page shows the generic invalid-credentials message and no account status
