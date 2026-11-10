# Tasks

## 1. Design tokens and typography

- [x] 1.1 Update `resources/css/app.css`: set `--primary` and `--sidebar-primary` to `#2A9263`, point `--ring` and `--chart-1` at the primary family, set `--background`/`--sidebar` to `#FAFAFA`, `--card`/`--popover` to `#FFFFFF`, and `--border`/`--sidebar-border` to `#E8E9E8`; verify `npm run build` succeeds and the compiled CSS resolves the new values.
- [x] 1.2 Change `--font-sans` to an Inter-first stack and replace the Figtree/Instrument Sans references in `resources/views/components/layout/app-shell.blade.php`, `resources/views/components/admin-layout.blade.php`, `resources/views/layouts/guest.blade.php`, and `vite.config.js`; verify page source requests Inter and contains no Figtree or Instrument Sans request.
- [x] 1.3 Update `docs/design_system.md` sections 3, 5, 6, 7, and 8 with the new primary `#2A9263`, Inter, sidebar tokens, sidebar width, content surface, and header measurements; verify every value matches `resources/css/app.css`.

## 2. Shared shell primitives

- [x] 2.1 Add `resources/views/components/layout/sidebar.blade.php` as the frame (aside, responsive classes, `id` prop, mobile close control) and verify it renders with `id="app-sidebar"` and `id="admin-sidebar"` when passed each value.
- [x] 2.2 Add `sidebar-brand`, `sidebar-search`, `sidebar-section`, `sidebar-link`, and `sidebar-footer` components under `resources/views/components/layout/`; verify a Blade template using each one compiles and renders the expected labels, icons, and `aria-current="page"` for the active link.
- [x] 2.3 Add the shared `layout/header` component with breadcrumb, page title, optional status slot, notifications, and profile menu; verify it renders the mobile `Buka menu` control and the `dusk="notification-bell"` marker.
- [x] 2.4 Rewrite `resources/views/components/layout/app-shell.blade.php` to use the new sidebar frame and a bordered, rounded content surface with the shared header; verify the response contains exactly one `<html>`, one `id="app-sidebar"`, and one content surface per page.

## 3. Role sidebars

- [x] 3.1 Move the donor sidebar onto the shared primitives and data: Dashboard, Posting Makanan, Klaim Masuk with claim count badge, Request Board, Ulasan dan Laporan (route-guarded), and Profil Saya; verify `php artisan test --filter=LayoutIntegrationTest` still sees `Donor Hub`, `Posting Makanan`, `Profil Saya`, and no `Katalog Makanan`.
- [x] 3.2 Move the recipient sidebar onto the shared primitives and data: Dashboard, Katalog Makanan, Klaim Saya, Request Board, Ulasan dan Laporan (route-guarded), and Profil Saya; verify the layout test still sees `Penerima / Yayasan`, `Katalog Makanan`, and `Profil Saya`.
- [x] 3.3 Move the admin sidebar onto the shared primitives with the `MENU UTAMA` group and a route-guarded `MANAJEMEN DATA` group; keep `id="admin-sidebar"` and existing verification/report badges; verify no sidebar anchor renders `href="#"`.

## 4. Admin shell migration

- [x] 4.1 Reduce `resources/views/components/admin-layout.blade.php` to a thin wrapper over the shared shell and move admin views to the shared layout without changing their page content; verify every admin route returns the shared shell with `id="admin-sidebar"` and no `id="app-sidebar"`.
- [x] 4.2 Remove the now-duplicated admin header/sidebar markup and confirm the admin header shows the breadcrumb, page title, pending-verification status, notifications, and profile menu; verify `php artisan test --filter=NotificationPageTest`.

## 5. Interaction behavior

- [ ] 5.1 Implement the Alpine navigation filter (case-insensitive label match, no-results message, Escape clears) and the `Cmd/Ctrl+K` focus shortcut; verify filtering, empty state, and shortcut behavior in a browser.
- [ ] 5.2 Keep the responsive drawer behavior: hidden below `lg`, opens from `Buka menu`, closes on Escape, backdrop, and link activation; verify at 375 px, 768 px, and 1440 px viewports.
- [ ] 5.3 Enforce route-guarded navigation everywhere, including the admin data group and `Ulasan dan Laporan`; verify no unavailable destination renders as an anchor with `href="#"`.

## 6. Verification

- [ ] 6.1 Add or update feature tests for the shared shell: one shell per page, role-specific sidebar IDs, active `aria-current`, route-guarded items, and the new header structure; verify `php artisan test`.
- [ ] 6.2 Run `php artisan dusk` (or the affected Browser tests) to confirm notification dropdown selectors and mobile drawer behavior still pass.
- [ ] 6.3 Visually compare administrator, donor, and recipient dashboards at 1920 px against `references/Dashboard Admin.png`, `references/Dashboard Donor.png`, and `references/Dashboard Penerima.png`; verify sidebar width, active pill, header, and content surface match the reference measurements, and record any intentional deviation (for example active-pill contrast).
