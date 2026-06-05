# TRK-07 Admin operations validation

## Type

Validation

## Depends On

TRK-03

## Blocks

TRK-08

## Parallel Safety

Safe to run in parallel with TRK-04, TRK-05, TRK-06.

## Objective

Validate all launch-critical admin actions.

## Checklist

- [ ] Access admin dashboard
- [ ] Launch a match result
- [ ] Reopen a match
- [ ] Assign knockout teams
- [ ] Edit knockout date and time
- [ ] Set tournament bonus answer key

## Done When

- Admin workflows operate without manual DB repair
- Score recalculation runs as expected

## Do Not Expand Scope

- Cosmetic admin improvements
- Result automation API integration
