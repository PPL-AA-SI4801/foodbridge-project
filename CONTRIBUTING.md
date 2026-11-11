# Contributing to FoodBridge

## Branch naming

Use a Jira-keyed prefix, e.g. `feature/PBI-12-claim-quota`, `fix/PBI-34-login-redirect`,
`chore/PBI-56-update-deps`. The key must match the issue you are working on.

## Commit messages

Use the standardized Jira-keyed format:

```
PBI-12: short imperative summary

Optional body explaining why, not what.
```

Every commit that lands tests or feature work MUST carry the Jira key. Do not use
generic messages such as `update`, `fix`, or `wip`.

## Pull request merge criteria

A PR may be marked **Ready to merge** only when ALL of the following hold:

1. **Data seeder present** — any feature touched has an idempotent, hand-written
   seeder (see `database/seeders/` and `database/seeders/README.md`) so a fresh
   `php artisan migrate:fresh --seed` exercises the feature. Idempotent means
   re-running `php artisan db:seed` does not duplicate rows.
2. **Laravel Dusk coverage** — the feature has Dusk (or affected browser) tests
   written and passing. Run them locally with `php artisan dusk` (or the targeted
   filter) before requesting review.
3. **Commit messages** use the Jira key format above.
4. **PHPUnit passes** — `php artisan test` is green locally.
5. **No debug code** (`dd()`, `console.log`, commented-out blocks).

If a PR is marked "finished" but any criterion above is not met, reviewers must
block the merge and the author must either complete the criterion or move the PR
back to draft / "still in progress".

## PR state

Declare one of these in the PR template:

- **Still in progress** (draft) — work ongoing, not ready for review.
- **Ready for review** — code complete, criteria may or may not be met yet.
- **Ready to merge** — all merge criteria above are checked.

## Code structure

Follow Controller → Service → Model. Enforce role access via the Donor / Penerima /
Admin middleware. Self-review before requesting review.
EOF