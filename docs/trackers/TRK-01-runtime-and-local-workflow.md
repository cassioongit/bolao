# TRK-01 Runtime and local workflow

## Type

Setup

## Depends On

None

## Blocks

TRK-02, TRK-03, TRK-04, TRK-05, TRK-06, TRK-07, TRK-08, TRK-09

## Parallel Safety

Do not run in parallel with critical-path environment changes.

## Objective

Standardize exactly how the project is started and tested locally using the same PHP model already used in `kartops`.

## Checklist

- [x] Confirm the local PHP executable path in your normal machine environment
- [x] Confirm the local startup command
- [x] Confirm the local database import command
- [x] Confirm the local base URL used during testing
- [x] Document the canonical runbook

## Done When

- A single local runbook exists
- Starting the app is no longer treated as an open question

## Notes

- Canonical runbook: [local-runbook.md](/Users/cassiomachado/Documents/Development/bolao/docs/local-runbook.md)
- Startup command: `php -S localhost:8050`
- Base URL: `http://localhost:8050`
- DB import commands are documented explicitly in the runbook
- This Codex session does not have `php` installed, so runtime validation must happen on the normal machine environment
