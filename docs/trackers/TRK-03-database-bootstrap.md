# TRK-03 Database bootstrap

## Type

Data

## Depends On

TRK-02

## Blocks

TRK-04, TRK-05, TRK-06, TRK-07

## Parallel Safety

Do not run validation cards while schema or seed are changing.

## Objective

Bootstrap the target database with schema and seed so the application is testable end to end.

## Checklist

- [ ] Import `sql/schema.sql`
- [ ] Import `sql/seed.sql`
- [ ] Verify users can be created
- [ ] Verify teams were loaded
- [ ] Verify matches were loaded
- [ ] Verify character set and timezone behavior

## Done When

- Database is live and usable
- Expected World Cup data is present

## Notes

- Import order is fixed: `sql/schema.sql` then `sql/seed.sql`
- Expected seed contents: 48 teams and 104 matches
- Character set target: `utf8mb4`
- Runtime timezone rule: persist in UTC, display in `America/Sao_Paulo`
- Suggested verification queries: `SELECT COUNT(*) AS teams_count FROM teams;`, `SELECT COUNT(*) AS matches_count FROM matches;`, `SELECT @@session.time_zone AS session_tz, @@global.time_zone AS global_tz;`
- This is the synchronization point before the parallel validation lanes start
