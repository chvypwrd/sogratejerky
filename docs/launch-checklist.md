# SOGRATE — Launch Checklist

Use this checklist before and after production deployment.

---

## Pre-Deploy

- [ ] Approved Git revision identified (commit hash)
- [ ] Dependency audits clean (`composer audit`, `npm audit`)
- [ ] Full test suite green (`php artisan test`)
- [ ] Pint passes (`./vendor/bin/pint --test`)
- [ ] Frontend builds (`npm run build`)
- [ ] Production `.env` prepared (see Production Environment section)
- [ ] Domain configured (DNS A/CNAME records)
- [ ] TLS certificate valid
- [ ] Database server ready (MySQL/MariaDB)
- [ ] Database backup plan documented
- [ ] SMTP/mail credentials ready
- [ ] Branding assets uploaded (logo, favicon, social image)
- [ ] Content reviewed (Pages, Testimonials, FAQs, Galleries)

---

## Deploy

- [ ] Place application in maintenance mode (`php artisan down --render="errors::503"`)
- [ ] Install Composer dependencies (`composer install --no-dev --prefer-dist --optimize-autoloader`)
- [ ] Install/build frontend dependencies (`npm ci && npm run build`)
- [ ] Verify writable directories (`storage/`, `bootstrap/cache/`)
- [ ] Verify/create public storage link (`php artisan storage:link`)
- [ ] Run migrations (`php artisan migrate --force`)
- [ ] Run optimization (`php artisan optimize`)
- [ ] Reload application services/processes if host requires
- [ ] Bring application online (`php artisan up`)

---

## Post-Deploy

- [ ] Run production check (`php artisan app:production-check`)
- [ ] Verify health endpoint (`GET /up` → 200)
- [ ] Test homepage
- [ ] Test /faq
- [ ] Test /contact (submit test message)
- [ ] Test /gallery, one Gallery detail
- [ ] Test one CMS Page
- [ ] Test /sitemap.xml (valid XML)
- [ ] Test /robots.txt (allows crawling)
- [ ] Test /admin/login
- [ ] Test admin dashboard
- [ ] Test admin Pages, Testimonials, FAQs, Galleries, Menus, Settings, Redirects, Contact inbox, Security
- [ ] Verify media assets (logo, favicon, images, Gallery thumbnails)
- [ ] Verify Contact email notification delivered
- [ ] Verify 404 page renders safely
- [ ] Check production logs for errors
- [ ] Verify no mixed content warnings

---

## Content / Brand

- [ ] Logo uploaded
- [ ] Favicon uploaded
- [ ] Social image uploaded
- [ ] Contact email configured
- [ ] Contact phone configured (if available)
- [ ] Contact address configured (if available)
- [ ] Social media URLs configured
- [ ] SEO default title/description set
- [ ] Google verification token configured (if applicable)
- [ ] Homepage content reviewed
- [ ] Pages populated
- [ ] Testimonials populated
- [ ] FAQs populated
- [ ] Galleries populated
- [ ] Menu navigation configured
- [ ] Footer text configured

---

## SEO

- [ ] `APP_URL` set to canonical HTTPS URL
- [ ] Canonical URLs verified (no localhost)
- [ ] Sitemap includes correct production URLs
- [ ] Robots.txt allows crawling (production)
- [ ] Google Search Console verification token set (if applicable)
- [ ] Admin pages noindexed
- [ ] Structured data parseable

---

## Mail / Contact

- [ ] Production SMTP configured and tested
- [ ] Contact form submission creates DB record
- [ ] Contact form submission sends admin notification
- [ ] Reply-To uses visitor email
- [ ] Spam protection active (honeypot, timing, rate limit, duplicate)

---

## Media

- [ ] Public storage link exists
- [ ] Logo accessible
- [ ] Favicon accessible
- [ ] Social image accessible
- [ ] Page/Class/Instructor images accessible
- [ ] Gallery thumbnails accessible
- [ ] Gallery viewer working
- [ ] Available disk space sufficient

---

## Security

- [ ] `APP_DEBUG=false`
- [ ] HTTPS enforced
- [ ] Secure session cookie (HttpOnly, Secure, SameSite=Lax)
- [ ] Admin 2FA enabled
- [ ] Registration absent
- [ ] Security headers present (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy)
- [ ] `.env` not publicly accessible
- [ ] Production logs private

---

## Backup

- [ ] Initial backup created
- [ ] Off-host storage configured
- [ ] Database backed up
- [ ] Media originals backed up
- [ ] Restore procedure documented
- [ ] Backup verified accessible

---

## Rollback

- [ ] Previous release revision preserved
- [ ] Migration compatibility reviewed
- [ ] Restore backup accessible
- [ ] Operator knows rollback procedure

---

## Production Environment

Required `.env` values:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=<generated, never in Git>
DB_CONNECTION=mysql
DB_HOST=<production host>
DB_PORT=3306
DB_DATABASE=<production database>
DB_USERNAME=<production user>
DB_PASSWORD=<production password>
MAIL_MAILER=smtp
MAIL_HOST=<smtp host>
MAIL_PORT=587
MAIL_USERNAME=<smtp username>
MAIL_PASSWORD=<smtp password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=<sender address>
MAIL_FROM_NAME="SOGRATE"
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
LOG_CHANNEL=daily
LOG_LEVEL=warning
CACHE_STORE=database
FILESYSTEM_DISK=public
```

---

## Initial Production Setup

One-time sequence for first launch:

1. Configure production environment
2. Generate APP_KEY (`php artisan key:generate`)
3. Run migrations (`php artisan migrate --force`)
4. Create storage link (`php artisan storage:link`)
5. Create admin account (`php artisan app:create-admin`)
6. Enable TOTP 2FA for admin
7. Configure site settings
8. Upload branding (logo, favicon, social image)
9. Populate content (Pages, FAQs, Galleries)
10. Configure menu navigation
11. Run optimization (`php artisan optimize`)
12. Create initial backup
13. Launch

---

## Routine Deployment

Subsequent releases:

1. Backup (if applicable)
2. Obtain approved release
3. `composer install --no-dev --prefer-dist --optimize-autoloader`
4. `npm ci && npm run build`
5. `php artisan migrate --force`
6. `php artisan optimize`
7. Reload services if needed
8. Verify `/up`

Do NOT regenerate APP_KEY or run `db:seed` during routine deployments.
