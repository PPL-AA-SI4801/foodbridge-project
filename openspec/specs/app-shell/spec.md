# app-shell Specification

## Purpose
Defines the shared authenticated application shell used by every role: the responsive sidebar region, role-specific navigation, the bordered content surface, and the role-aware page header.

## Requirements

### Requirement: Single shared authenticated shell
All authenticated role dashboards (administrator, donor, recipient) SHALL render through one shared shell that provides the document scaffold, sidebar region, content surface, and header. A page MUST NOT load a second independent document shell.

#### Scenario: Administrator uses the shared shell
- **WHEN** an administrator opens an admin page
- **THEN** the page renders the same shell structure, content surface, and header placement used by donor and recipient pages

#### Scenario: Only one document shell per page
- **WHEN** any authenticated page is rendered
- **THEN** the response contains exactly one document scaffold and one sidebar region

### Requirement: Role-specific sidebar navigation
The shared shell SHALL delegate sidebar navigation content to the sidebar for the authenticated user's role, so administrator, donor, and recipient each see only their own menu items.

#### Scenario: Administrator menu
- **WHEN** an administrator views the sidebar
- **THEN** it shows dashboard, verification, reports, request board, and data-management navigation appropriate to administration

#### Scenario: Donor menu
- **WHEN** a donor views the sidebar
- **THEN** it shows dashboard, food posting, incoming claims, request board, and review/report navigation appropriate to donors

#### Scenario: Recipient menu
- **WHEN** a recipient views the sidebar
- **THEN** it shows dashboard, food catalog, their claims, request board, and review/report navigation appropriate to recipients

### Requirement: Sidebar composition
Every role sidebar SHALL present, in order, the FoodBridge brand, an optional navigation search field, one or more labelled navigation groups, and a footer that shows the signed-in user's identity and a logout control.

#### Scenario: Sidebar footer identifies the user
- **WHEN** a signed-in user views any sidebar
- **THEN** the footer shows their display name, email, and a logout action

#### Scenario: Navigation groups are labelled
- **WHEN** a sidebar groups its links
- **THEN** each group has a visible section label above its links

### Requirement: Active navigation state
The sidebar SHALL mark exactly one current destination as active, expose it with `aria-current="page"`, and style it distinctly from inactive links.

#### Scenario: Active link is announced and styled
- **WHEN** the current route matches a sidebar destination
- **THEN** that destination is styled as active and carries `aria-current="page"`

#### Scenario: Only one active destination
- **WHEN** multiple sidebar links could match a route prefix
- **THEN** only the single best-matching destination is marked active

### Requirement: Navigation search
When a sidebar provides a search field, typing SHALL filter the visible navigation links by their labels without a page reload, and the `Cmd/Ctrl+K` shortcut SHALL move focus to that field.

#### Scenario: Filtering navigation
- **WHEN** a user types part of a navigation label into the sidebar search
- **THEN** only links whose labels match the typed text remain visible

#### Scenario: No matching navigation
- **WHEN** the typed text matches no navigation label
- **THEN** the sidebar shows an explicit empty result message instead of leaving stale links visible

#### Scenario: Keyboard shortcut focuses search
- **WHEN** the user presses `Cmd+K` or `Ctrl+K` while the shell is focused
- **THEN** focus moves to the sidebar search field

### Requirement: Responsive sidebar drawer
Below the desktop breakpoint the sidebar SHALL be hidden by default and open as an overlay drawer; at and above the desktop breakpoint it SHALL remain persistently visible.

#### Scenario: Opening the drawer on a small screen
- **WHEN** a user activates the menu control on a small screen
- **THEN** the sidebar opens over the content and exposes a close control

#### Scenario: Dismissing the drawer
- **WHEN** the drawer is open and the user presses Escape, activates the backdrop, or follows a navigation link
- **THEN** the drawer closes

#### Scenario: Persistent on desktop
- **WHEN** the viewport is at or above the desktop breakpoint
- **THEN** the sidebar is visible without a menu toggle and the drawer backdrop is not shown

### Requirement: Content surface and page header
The shell SHALL place page content inside a bordered, rounded surface inset from the page background. The page header SHALL sit at the top of that surface, show a breadcrumb and page title on the left, and expose notification, profile, and optional status controls on the right.

#### Scenario: Header identifies the page
- **WHEN** a user opens a page with a title
- **THEN** the header shows a breadcrumb trail and the page title within the content surface

#### Scenario: Header actions stay available
- **WHEN** any authenticated page is shown
- **THEN** the header exposes notifications and the profile menu regardless of role

#### Scenario: Role-specific status summary
- **WHEN** the active role provides a status summary such as pending verifications or daily claim quota
- **THEN** the header shows that summary next to the notification and profile controls

### Requirement: Route-guarded navigation
Sidebar destinations whose routes are unavailable SHALL NOT render as inert links pointing to `#`. They MUST be omitted or rendered as disabled items without an `href`.

#### Scenario: Unavailable feature is not linked
- **WHEN** a sidebar item refers to a route that is not registered
- **THEN** the shell does not render an anchor with `href="#"` for that item

#### Scenario: Available feature is linked
- **WHEN** a sidebar item refers to a registered route
- **THEN** the item links to that route
