# TRK-08 Launch smoke test

## Type

Integrated validation

## Depends On

TRK-04, TRK-05, TRK-06, TRK-07

## Blocks

TRK-09

## Parallel Safety

Run after the four validation lanes are done. Do not overlap with schema changes.

## Objective

Run one realistic end-to-end scenario on the final target environment.

## Checklist

- [ ] Register user A
- [ ] Create a pool
- [ ] Register or log in user B
- [ ] Join user B by invite
- [ ] Save predictions
- [ ] Save bonus picks
- [ ] Launch a result as admin
- [ ] Confirm ranking update
- [ ] Confirm no manual DB intervention was needed

## Done When

- One full scenario works end to end
- Remaining launch-critical defects are known
