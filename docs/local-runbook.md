# Local Runbook

## Current Reality

- This Codex session does not have `php` installed, so local execution must happen in your normal machine environment.
- The project is already wired for the built-in PHP server and a MySQL database configured through `.env`.
- Canonical base URL for local testing: `http://localhost:8050`

## One-Time Setup

1. Copy `.env.example` to `.env`.
2. Set `APP_ENV=local`.
3. Fill `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` with your MySQL credentials.
4. Keep `MAIL_DRIVER=log` for local testing so password reset e-mails are written to `nimbalyst-local/outbox/`.

## Database Bootstrap

Import the schema first and the seed second:

```bash
mysql -h HOST -P 3306 -u USER -p DB_NAME < sql/schema.sql
mysql -h HOST -P 3306 -u USER -p DB_NAME < sql/seed.sql
```

## Start The App

Run this from the project root:

```bash
php -S localhost:8050
```

Then open:

```text
http://localhost:8050
```

## Expected First Checks

- Register the first user and confirm it becomes admin.
- Confirm the dashboard opens after login.
- Confirm password reset writes an HTML file under `nimbalyst-local/outbox/`.

## Notes

- `includes/config.php` auto-detects `APP_URL` when not set, but local work should keep `APP_URL=http://localhost:8050` to avoid ambiguity.
- The application stores timestamps in UTC and displays them in `America/Sao_Paulo`.
