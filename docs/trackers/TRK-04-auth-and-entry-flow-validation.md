# TRK-04 Auth and entry flow validation

## Type

Validation

## Depends On

TRK-03

## Blocks

TRK-08

## Parallel Safety

Safe to run in parallel with TRK-05, TRK-06, TRK-07.

## Objective

Validate the user entry flows without mixing in betting, ranking, or admin concerns.

## Checklist

- [ ] Register a new user
- [ ] Log in with an existing user
- [ ] Log out
- [ ] Validate redirect-after-login
- [ ] Validate password reset behavior

## Done When

- A new user can enter the dashboard
- A returning user can authenticate normally
- Password reset behavior is known and accepted for launch

## Do Not Expand Scope

- Pool creation
- Predictions
- Admin scoring
