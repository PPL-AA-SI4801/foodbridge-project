# Proposal

## Why

The application currently ships three divergent shell implementations: donor and recipient screens use `x-layout.app-shell`, while the admin area uses a separate `admin-layout` with its own header and sidebar. The three sidebar components also duplicate markup and styling, so every navigation change has to be made three times. The redesign consolidates this into one shell architecture while giving each role its own sidebar content, and aligns the interface with the supplied dashboard reference images (`references/Dashboard Admin.png`, `references/Dashboard Donor.png`, `references/Dashboard Penerima.png`).

## What Changes

- Replace the split `app-shell` / `admin-layout` approach with one shared shell used by all roles. Admin screens migrate onto the shared shell.
- Extract shared sidebar primitives (frame, brand block, search field, section label, nav link, badge, footer card) so the donor, recipient, and admin sidebars only supply their own navigation data.
- Redesign each sidebar independently to match its reference image while sharing the same component architecture: logo, sidebar search with `Cmd/Ctrl+K` affordance, `MENU UTAMA` section, role-specific links, optional grouped `MANAJEMEN DATA` section for admin, and a footer user card with logout.
- Redesign the content frame: a bordered, rounded white content surface inset from the page background, with a two-tier header inside it (breadcrumb + page title on the left, status/notification/profile controls on the right).
- Add a shared dashboard header component driven by role, replacing the ad-hoc headers in `app-shell` and `admin-layout`.
- Change the brand primary color from `#133830` to `#2A9263` across tokens (primary, ring, sidebar primary, chart 1) while keeping the existing button treatment (inner gradient stroke, layered shadows, hover/active/focus states) unchanged.
- Standardize typography on Inter, replacing the current mix of Instrument Sans, Figtree, and design-document references.
- Keep route-guarded navigation: links whose routes do not exist (for example `Ulasan dan Laporan`, `Data Pengguna`, `Klaim`, `Kategori Gizi`) render only when their routes are available. Existing functional links such as `Katalog Makanan` are preserved even where a reference image omits them.
- **BREAKING**: the admin area no longer uses `x-admin-layout`; any view relying on that component must move to the shared layout.

## Capabilities

### New Capabilities
- `app-shell`: one shared authenticated layout for administrator, donor, and recipient roles, including the content surface, role-aware header, responsive sidebar drawer, and shared sidebar building blocks.
- `design-system`: the visual token contract for the application: primary `#2A9263`, the derived primary scale, sidebar tokens, border/background tokens used by the new shell, and Inter as the single sans-serif family.

### Modified Capabilities
- None. No existing spec describes layout, navigation, or visual tokens.

## Impact

- Layouts and components: `resources/views/components/layout/{app-shell,header,sidebar,sidebar-link,page-header}.blade.php`, `resources/views/components/{admin-layout}.blade.php`, `resources/views/components/{donor,recipient,admin}/sidebar.blade.php`, `resources/views/components/header/{profile-menu,notifications}.blade.php`, `resources/views/layouts/app.blade.php`, and the views that pass `title` / `header` / actions to them.
- Styling: `resources/css/app.css`, `resources/css/button.css`, and the design documentation in `docs/design_system.md`.
- Font loading in both shared and guest layouts.
- New sidebar primitives and a shared header component; no route, controller, model, or migration changes.
- Route-guarded links mean unreleased features stay hidden rather than linking to `#`.
- Out of scope: adding the search backend, building `Ulasan dan Laporan`, `Data Pengguna`, `Klaim`, or `Kategori Gizi` features, and changing business logic.
