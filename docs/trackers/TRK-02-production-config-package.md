# TRK-02 Production config package

## Type

Ops

## Depends On

TRK-01

## Blocks

TRK-03

## Parallel Safety

Safe to run alone. Avoid concurrent edits to production config assumptions.

## Objective

Prepare the exact production configuration package needed for deployment on the target shared hosting.

## Checklist

- [x] Finalize `.env` values for production
- [x] Confirm `APP_URL`
- [x] Confirm `APP_ENV=production`
- [x] Confirm `SHOW_ERRORS=false`
- [x] Decide launch behavior for mail-dependent flows
- [x] Produce a short FTP deploy checklist

## Done When

- Production config can be applied without guessing
- Deployment steps are unambiguous

## Notes

- Production checklist: [production-deploy-checklist.md](/Users/cassiomachado/Documents/Development/bolao/docs/production-deploy-checklist.md)
- Launch recommendation: `REQUIRE_EMAIL_VERIFICATION=false`
- Launch recommendation: `MAIL_DRIVER=mail` only if the host's PHP `mail()` path is verified
- Hosting model is already validated by `kartops`
