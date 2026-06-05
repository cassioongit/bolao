# TRK-05 Pool and invite validation

## Type

Validation

## Depends On

TRK-03

## Blocks

TRK-08

## Parallel Safety

Safe to run in parallel with TRK-04, TRK-06, TRK-07.

## Objective

Validate the private pool loop from creation through joining and member management.

## Checklist

- [ ] Create a pool
- [ ] Copy the invite link
- [ ] Join from invite as another user
- [ ] Validate members list
- [ ] Validate owner removal flow

## Done When

- User A can create a pool
- User B can join using the invite
- Both users see consistent membership state

## Do Not Expand Scope

- Ranking logic
- Match result logic
