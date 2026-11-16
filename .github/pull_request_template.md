## Summary
<!-- What does this PR do, and why? 1-3 sentences. -->

## Related Issue
<!-- Jira PBI key so the PR links to the board, e.g. FB-12 -->
Closes: PBI-

## PR State
<!-- Declare the current state. -->
- [ ] Still in progress (draft)
- [ ] Ready for review
- [ ] Ready to merge (all merge criteria below are met)

## Merge Criteria (must all be checked to merge)
- [ ] Idempotent, hand-written data seeder present for touched features
- [ ] Laravel Dusk (or affected browser) tests written and passing
- [ ] Commit messages use the Jira key format (e.g. `PBI-12: ...`)
- [ ] `php artisan test` passes locally
- [ ] No debug code or commented-out code

> If you mark "Ready to merge" without the criteria above, reviewers will block
> the PR and move it back to draft.

## Type of Change
- [ ] Feature
- [ ] Bug fix
- [ ] Refactor
- [ ] Docs
- [ ] Chore (config, dependencies, CI)

## Changes
<!-- Main changes, one bullet each. -->
-
-

## Screenshots / Recordings
<!-- For UI changes. Show before/after and mobile view if relevant. Delete if N/A. -->

## How to Test
<!-- Steps for the reviewer. -->
1.
2.
3.

**Expected result:**

## Checklist
- [ ] Branch name follows convention (e.g. `feature/FB-12-claim-quota`)
- [ ] Code follows project structure (Controller → Service → Model)
- [ ] Role access checked (Donor / Penerima / Admin middleware)
- [ ] Self-reviewed my own code
- [ ] No debug code (`dd()`, `console.log`) or commented-out code
- [ ] Tested on desktop and mobile view
- [ ] Tests pass locally (`php artisan test` / `php artisan dusk`)
- [ ] Jira status updated

## Notes for Reviewer
<!-- Anything risky, unfinished, or worth a closer look. Delete if N/A. -->
