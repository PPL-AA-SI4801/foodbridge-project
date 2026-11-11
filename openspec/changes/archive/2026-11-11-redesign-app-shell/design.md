# Design

## Context

See `proposal.md` for motivation. The current implementation has two document shells and three sidebar components:

- `resources/views/layouts/app.blade.php` → `x-layout.app-shell` (donor, recipient)
- `resources/views/components/admin-layout.blade.php` (admin, own `<html>`, header, sidebar)
- `resources/views/components/layout/sidebar.blade.php` plus per-role `donor/sidebar`, `recipient/sidebar`, `admin/sidebar`

Existing tests constrain the refactor: `tests/Feature/LayoutIntegrationTest.php` and `tests/Feature/NotificationPageTest.php` assert `id="app-sidebar"` for donor/recipient and `id="admin-sidebar"` for admin (and not the other), plus text such as `Donor Hub`, `Penerima / Yayasan`, `Posting Makanan`, `Katalog Makanan`, `Profil Saya`, and `Buka menu`. These identifiers and strings must survive the redesign.

The reference images are 5760×4080 PNGs (a 3× export of a 1920×1360 canvas). Measurements below are derived from pixel analysis of `references/Dashboard Admin.png` and cross-checked against the donor and recipient exports, which share the same frame.

## Goals / Non-Goals

**Goals:**

- One shell architecture, three role sidebar configurations.
- Match the reference frame: light sidebar, green active pill, search field, grouped navigation, footer user card, bordered white content surface, two-tier header.
- Make the primary color and typography token-driven so `#2A9263` and Inter are configured once.

**Non-Goals:**

- Building any new feature routes (`Ulasan dan Laporan`, `Data Pengguna`, `Klaim`, `Kategori Gizi`) or a global search backend.
- Changing dashboard page bodies beyond wrapping them in the new content surface.
- Adding dark mode, animations beyond existing hover/transition behavior, or a mobile bottom navigation.
- Redesigning the guest/auth pages.

## Decisions

### 1. One shell, role sidebars as content

`x-layout.app-shell` becomes the only document shell. It owns `<html>`, `<head>`, the Alpine sidebar state, the backdrop, the content surface, and the header. The role sidebar is selected exactly as today (donor → `x-donor.sidebar`, recipient → `x-recipient.sidebar`, else → `x-admin.sidebar`).

`x-admin-layout` is reduced to a thin delegating wrapper over `app-shell` so existing admin views keep working during migration, then admin views are moved to `x-app-layout` / `x-layout.app-shell` directly. Duplicated `<html>`/header markup is deleted.

**Alternative considered:** keep two shells and only restyle each. Rejected because the header, content surface, and responsive drawer would still be duplicated, which is the root problem.

### 2. Shared sidebar primitives

Add presentational components under `resources/views/components/layout/`:

- `sidebar` — the `<aside>` frame, responsive classes, backdrop coordination, mobile close button
- `sidebar-brand` — logo + mobile close control
- `sidebar-search` — search field with the `Cmd/Ctrl+K` hint
- `sidebar-section` — uppercase group label
- `sidebar-link` — icon, label, active pill, optional badge, `aria-current`
- `sidebar-footer` — user card (avatar, display name, email) and logout form

Each role sidebar supplies only its navigation data and footer identity. The frame accepts an `id` prop so donor/recipient keep `app-sidebar` and admin keeps `admin-sidebar`; this preserves the existing tests and avoids duplicate IDs.

The generic `components/layout/sidebar.blade.php` is retired or rewritten as the frame primitive; the per-role sidebars stop duplicating frame markup.

### 3. Reference measurements

| Element | Reference (3× pixels) | Implementation value |
|---|---|---|
| Sidebar width | 1144 px | ~380 px (`23.75rem`) at `lg` and up |
| Sidebar background | `#FAFAFA` | `--sidebar` |
| Sidebar border/divider | `#E8E9E8` | `--sidebar-border` / `--border` |
| Active pill | `#2A9263`, white text | `--sidebar-primary` / `--primary` |
| Active pill height | 160 px | ~52 px |
| Badge orange | `#D26C29` | keep `--secondary` (`#D97736`) unless a later palette pass aligns it |
| Content surface | `#FFFFFF`, border `#E8E9E8`, radius | `--card` + `--border` + `--radius` |
| Page background | `#FAFAFA` | `--background` |
| Content surface inset | 64 px top/right/bottom | ~22 px (`1.375rem`) top/right/bottom, flush to sidebar |
| Header height | 312 px | ~104 px |
| Header divider | `#E8E9E8` | `--border` |

The page uses a light sidebar flush to the viewport edge and a content surface that starts at the sidebar border with top/right/bottom insets. On small screens the surface insets become uniform.

### 4. Header composition

A shared `layout/header` component receives `title`, `breadcrumb`, optional `status` slot, and always renders notifications and the profile menu. Left column stacks a small breadcrumb (`FoodBridge > <page>`) above the page title; right column holds the role status pill (admin: pending verifications; recipient: daily quota), notification bell, and profile menu.

Existing `Donor Hub` / `Penerima / Yayasan` role labels move into a compact label near the title or breadcrumb so `LayoutIntegrationTest` keeps passing without showing a large role badge in the reference layout does not have. The mobile menu button keeps its `Buka menu` label.

### 5. Sidebar search is a navigation filter, not global search

Alpine owns a `query` value; visible links are filtered client-side by label. `Cmd/Ctrl+K` focuses the field, Escape clears it, and a no-results message appears when nothing matches. No route or backend is added.

**Alternative considered:** render the search box as a disabled visual placeholder. Rejected because a non-functional control is misleading; filtering the menu is cheap and honest.

### 6. Route-guarded navigation

Role sidebars keep a `Route::has()` check and omit unavailable destinations. The admin data group only renders links whose routes exist. This fixes the current admin sidebar rendering `#` links for `admin.users`, `admin.claims`, and `admin.categories`, which have no registered routes.

Existing functional items are preserved even when the reference image omits them: donor/recipient keep `Profil Saya`, and recipient keeps `Katalog Makanan`. `Ulasan dan Laporan` is added as a guarded item and stays hidden until its routes exist.

### 7. Token changes

`resources/css/app.css`:

- `--primary: #2A9263`; keep the `color-mix` scale (`--primary-300/600/700`) so hover and focus derive automatically.
- `--ring`, `--sidebar-primary`, `--chart-1` point at the new primary.
- `--background: #FAFAFA`, `--card`/`--popover` `#FFFFFF`, `--border`/`--sidebar-border` `#E8E9E8`, `--sidebar` `#FAFAFA`.
- `--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif`.
- Keep `--secondary` and status badge colors unchanged for this change.

`resources/css/button.css` needs no color edits because it reads `--primary-*`; the gradient stroke, layered shadow, radius, and interaction states stay byte-for-byte.

Layouts load Inter from `fonts.bunny.net` instead of Figtree, and remove the unused Instrument Sans reference.

### 8. Accessibility and behavior

- Preserve `aria-current`, `aria-controls`, `aria-expanded`, and the mobile backdrop.
- Keep the notification `dusk="notification-bell"` selector and `data-unread-count` attribute used by Dusk tests.
- Close the mobile drawer on Escape, backdrop click, and link activation.
- Do not add a focus trap in this change; the existing Escape/backdrop behavior is retained to keep scope small.

## Risks / Trade-offs

- **[Active pill contrast]** White text on `#2A9263` is about 3.9:1, below WCAG AA for 14 px text. Mitigation: document this as a deliberate reference-match exception; if strict AA is required, use `--primary-600` for the active pill only, which keeps the button color at `#2A9263`.
- **[Wide sidebar]** 380 px is much wider than the current 256 px and reduces content width on 1024–1280 px screens. Mitigation: apply 380 px only at `lg` and up, keep the existing off-canvas drawer below `lg`, and verify at 1280 px and 1440 px.
- **[Test breakage]** Moving admin to the shared shell can break tests that assert `admin-sidebar`. Mitigation: keep the `id` prop and preserve the strings listed in Context; run the layout and notification test suites as the first verification step.
- **[Mockup-only features]** `Ulasan dan Laporan`, `Data Pengguna`, `Klaim`, and `Kategori Gizi` have no routes. Mitigation: render them only when routes exist; do not create dead links or new features in this change.
- **[Token cascade]** Changing `--primary` affects links, focus rings, chart accents, and the profile avatar, not just buttons. Mitigation: audit uses of `bg-primary`, `text-primary`, `ring`, and `chart-1` after the token change.
- **[Font loading]** Inter is a new network dependency. Mitigation: use `display=swap`, keep the system-font fallback chain, and load weights 400/500/600/700 only.

## Migration Plan

1. Add the new tokens and Inter loading; confirm the button still renders with its existing treatment.
2. Introduce the shared sidebar primitives and header component without changing role sidebars' data.
3. Convert `app-shell` to the new frame and migrate donor/recipient sidebars onto the primitives.
4. Reduce `admin-layout` to a wrapper, move admin pages to the shared shell, and keep `admin-sidebar`.
5. Run the feature/Browser test suites, then visually compare each role at 1920 px against its reference image.

Rollback is a revert of the layout/component files and token block; no database or route changes are involved.
