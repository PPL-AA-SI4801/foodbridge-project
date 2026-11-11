# Spec Delta

## Purpose

Defines the visual token contract for FoodBridge: the brand primary color and its derived scale, the sidebar and content-surface colors used by the shared shell, and Inter as the single sans-serif family.

## ADDED Requirements

### Requirement: Brand primary color
The application SHALL use `#2A9263` as `--primary`, and the tokens derived from it (`--primary-300`, `--primary-500`, `--primary-600`, `--primary-700`, `--ring`, `--sidebar-primary`, `--chart-1`) SHALL resolve from that value.

#### Scenario: Primary button color
- **WHEN** a primary button is rendered
- **THEN** its background resolves to `#2A9263`

#### Scenario: Focus ring color
- **WHEN** an interactive element receives keyboard focus
- **THEN** its focus ring uses the primary color family

#### Scenario: Derived shades stay in sync
- **WHEN** `--primary` changes
- **THEN** the hover, focus, and darker shades change with it without editing hardcoded hex values

### Requirement: Primary button treatment is preserved
Changing the primary color SHALL NOT change the primary button's treatment. The button MUST retain its inner gradient stroke, layered shadow, radius, hover shade, active scale, focus-visible outline, and disabled state behavior.

#### Scenario: Button keeps its depth treatment
- **WHEN** a primary button is rendered with the new color
- **THEN** it still shows the gradient inner stroke and layered shadow

#### Scenario: Interaction states still work
- **WHEN** a user hovers, presses, focuses, or disables a primary button
- **THEN** the existing hover, active, focus-visible, and disabled behavior is preserved

### Requirement: Inter typography
The application SHALL use Inter as its single sans-serif family for body text, headings, buttons, and form controls, and MUST NOT request the previously used Instrument Sans or Figtree families.

#### Scenario: Body text renders in Inter
- **WHEN** any application page loads
- **THEN** its computed base font family resolves to Inter

#### Scenario: Legacy fonts are not loaded
- **WHEN** an application page loads
- **THEN** it does not request Instrument Sans or Figtree font files

### Requirement: Sidebar visual tokens
The sidebar SHALL use `#FAFAFA` as its background, `#E8E9E8` as its border and divider color, the primary color as its active-item background, and `#FFFFFF` with a bordered rounded surface for the user footer card.

#### Scenario: Sidebar surfaces match the reference
- **WHEN** a user views any role sidebar
- **THEN** its background, dividers, active item, and footer card use the sidebar token set

### Requirement: Content surface tokens
The page background SHALL use `#FAFAFA`, and the content surface SHALL use `#FFFFFF` with an `#E8E9E8` border and rounded corners.

#### Scenario: Content surface stands apart from the page
- **WHEN** a user views an authenticated page
- **THEN** the content surface is visually distinct from the surrounding page background through its border and radius

### Requirement: Visual changes stay token-driven
Component styles SHALL reference the shared color and typography tokens rather than repeating literal color values, so later palette changes do not require per-component edits.

#### Scenario: A token change propagates
- **WHEN** a shared color token is changed
- **THEN** components using that token update without editing each component
