# TRK-06 Predictions and bonus validation

## Type

Validation

## Depends On

TRK-03

## Blocks

TRK-08

## Parallel Safety

Safe to run in parallel with TRK-04, TRK-05, TRK-07.

## Objective

Validate the core user betting experience before release.

## Checklist

- [ ] Save a single prediction
- [ ] Validate autosave behavior
- [ ] Validate default prediction behavior
- [ ] Validate fill-visible-screen behavior
- [ ] Validate lock-before-kickoff behavior
- [ ] Save bonus picks
- [ ] Re-open bonus picks and verify persistence

## Done When

- Predictions save correctly
- Locked matches reject changes server-side
- Bonus picks persist correctly

## Do Not Expand Scope

- Admin launch of final scores
- End-to-end ranking correctness
