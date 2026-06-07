# Production Deploy Checklist

## Launch Configuration

Use these values in production `.env`:

```dotenv
APP_ENV=production
APP_URL=https://SEU-DOMINIO
LOCK_MINUTES=5
DISPLAY_TZ=America/Sao_Paulo
REQUIRE_EMAIL_VERIFICATION=false
FIRST_USER_IS_ADMIN=true
MAIL_DRIVER=mail
SHOW_ERRORS=false
```

Also set:

- `DB_HOST`
- `DB_PORT=3306`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `MAIL_FROM_NAME`
- `MAIL_FROM_EMAIL`

## Pre-Deploy Decisions

- Launch with `REQUIRE_EMAIL_VERIFICATION=false` to avoid blocking onboarding on shared-hosting mail reliability.
- Launch with `MAIL_DRIVER=mail` only if the host can send PHP `mail()` successfully.
- If host mail is unreliable, keep password reset as an admin-assisted/manual support flow until SMTP or another provider is wired.

## FTP Deploy Steps

1. Create the MySQL database and database user in the hosting panel.
2. Import `sql/schema.sql` in phpMyAdmin.
3. Import `sql/seed.sql` in phpMyAdmin.
4. Upload the full project to the public web root by FTP.
5. Create `.env` on the server with the production values above.
6. Confirm the web server does not expose `.env` or `sql/`.
7. Open the site and register the first account.
8. Confirm that first account is admin.
9. Create the first pool and copy the invite link for real users.

## Post-Deploy Smoke Checks

- Registration works.
- Login works.
- Pool creation works.
- Invite join works.
- A prediction can be saved.
- Bonus picks can be saved and reopened before the tournament lock.
- Admin can open the admin area.
- Admin can launch a result and see ranking update without manual DB repair.
- At least one multiplier-bearing stage result is validated in ranking output.
- Ranking ties follow the official precedence order.

## Operational Notes

- `cron/fetch_results.php` is not part of launch; result entry is manual in v1.
- `SHOW_ERRORS=false` is mandatory for launch so PHP warnings are not exposed to users.
- Keep a copy of the final production `.env` values outside the repo.
- `APP_URL` must match the exact public host used by end users to avoid redirect and session inconsistencies.
