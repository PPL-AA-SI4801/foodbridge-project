# Design

## Context

Greenfield Laravel 13 project (PHP `^8.3`, Vite 8 + Tailwind 4). The audience is beginner contributors who already run **Laragon or XAMPP** (their MySQL + phpMyAdmin habits are assets, not obstacles). A Docker-based approach was planned and deliberately abandoned (see proposal.md — motivation); the maintainer chose **README-only onboarding with no automation scripts**. The current `README.md` is the stock Laravel skeleton text — no project setup docs exist. Current defaults are SQLite (`.env.example`), with a `composer setup` script that already encodes the intended first-run flow. Requirements are in `specs/`.

## Goals / Non-Goals

**Goals:**
- A fresh clone + the README = running app, using only the contributor's existing Laragon/XAMPP MySQL.
- `.env.example` connects out of the box against stock Laragon/XAMPP credentials.
- Project scripts stop referencing SQLite so nothing contradicts the new default.

**Non-Goals:**
- Docker or any containerization (explicitly abandoned by maintainer decision).
- Setup/doctor automation scripts (`setup.bat`, `check-env`) — accepted escalation path later if the README proves insufficient (recorded so it's not re-litigated here).
- Composer `platform` pinning for lockfile drift (accepted risk, documented in README versions instead).
- Production deployment, CI, or multi-environment `.env` management.
- Migrating existing SQLite data to MySQL.

## Decisions

**D1 — README-only onboarding; scripts explicitly rejected.**
Maintainer's call: friends already have working local stacks, and a written walkthrough of tools they know beats a new abstraction layer. *Alternative rejected (recorded):* `setup.bat`/`check-env` middle path — kept as the documented escalation if the same support question repeats ≥3×, but not built now.

**D2 — Use their world's defaults verbatim: `root` / empty password / `127.0.0.1:3306`.**
Stock Laragon and XAMPP both ship MySQL as root with no password on 3306 — zero-config match beats "secure custom credentials" for a local-only beginner setup. *Alternative rejected:* dedicated `foodbridge` DB user — more secure in theory, but adds a phpMyAdmin privilege-management step every friend must get right; local-only exposure is loopback.

**D3 — Database name `foodbridge`, collation `utf8mb4_unicode_ci`.**
Named via README phpMyAdmin step (Laravel convention defaults). Explicit collation stated to avoid the classic "table charset mismatch" class of bugs on MariaDB (Laragon default) vs MySQL 8 (XAMPP).

**D4 — README structure mirrors the contributor's actual journey**, ordered: versions table → Laragon path **or** XAMPP path (branching early, rejoining at phpMyAdmin) → first-run commands (Windows + Unix variants side by side) → troubleshooting keyed by *symptom first* (`'php' is not recognized...`, `Class "PDO" not found`, `Address already in use`, missing `APP_KEY`). Symptom-first matches how beginners search. The "why these exact versions" paragraph sits at the top so the lecture exists once, in writing.

**D5 — Keep SQLite only as a commented, clearly-labeled alternative in `.env.example`** — maintainers/CI can still use it without confusing beginners (the active block is unambiguously MySQL).

**D6 — `composer.json` cleanup**: drop the `post-create-project-cmd` sqlite `touch` line; keep everything else (the `setup` script's `migrate --force` already works against MySQL once `.env` is set).

**D7 — Existing-contributor migration is a README upgrade note**, not a breaking-code change: "your old `.env` may still say sqlite — switch the DB block to MySQL defaults and re-run migrate." Nothing in the repo hard-requires it; the change only flips the template.

**D8 — Version policy: PHP loose, Composer/Node newest-but-stable.**
PHP stays at `^8.3` (accepting 8.3–8.5): Laragon/XAMPP users switch PHP versions in one menu click, so constraining them to exactly 8.5 buys little and costs onboarding friction. Composer (`2.10+`) and Node (`24 LTS+`, the newest LTS as of Oct 2026) have no such easy in-app switching for beginners — they must install them anyway, so the README asks for newest-at-writing, and `package.json` `engines` (`>=24`) gives npm a soft enforcement warning. Node 26.12 is "Current" and becomes LTS in late Oct 2026; `>=24` already accepts it. *Alternative rejected:* pinning all four tools to exact versions — over-constrains PHP for no benefit (recorded per maintainer decision).

## Risks / Trade-offs

- [README is the *only* guarantee — a friend who skims past a step is stuck] → symptom-first troubleshooting section covers the five known failure modes; maintainer accepts helpdesk role for anything else (accepted, documented in proposal).
- [No `platform` pin: lockfile may resolve deps for a slightly different PHP patch] → `composer.json` requires `^8.3`; README pins the exact version; low impact within a single minor line.
- [Empty root password in `.env.example` looks alarming to security-minded reviewers] → clearly scoped as "local Laragon/XAMPP stock credentials"; README notes it's local-only.
- [Laragon defaults to MariaDB, XAMPP to MySQL — version behavior can differ] → Laravel supports both; collation stated explicitly (D3); README lists "MySQL 8 or MariaDB 10.x" as acceptable.
- [Existing SQLite `.env` files silently keep working locally until the DB block is edited — confusion, not breakage] → D7 upgrade note in README.
- [Port 8000 conflicts with whatever else a beginner has running] → troubleshooting entry with `php artisan serve --port=8080` alternative.

## Migration Plan

Purely additive/flip-the-default: README section, `.env.example` DB block, one line removed from `composer.json`. Existing contributors: update `.env` DB block → `php artisan migrate`. Rollback: revert three files; no data or schema was touched by the change itself.

## Open Questions

- None blocking. (Whether to later add `setup.bat`/`check-env` scripts is deliberately deferred and recorded under Non-Goals.)
