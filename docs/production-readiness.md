# Production Readiness Guide

## Environment Requirements

### Required `.env` Settings

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_ENV` | `production` | Must be `production` for live |
| `APP_DEBUG` | `false` | Never enable in production |
| `APP_URL` | `https://yourdomain.com` | Canonical HTTPS URL |
| `APP_KEY` | (generated) | Run `php artisan key:generate` |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | (your DB host) | |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | (your DB name) | |
| `DB_USERNAME` | (your DB user) | |
| `DB_PASSWORD` | (your DB password) | |
| `MAIL_MAILER` | `smtp` | Or your chosen transport |
| `MAIL_FROM_ADDRESS` | `noreply@yourdomain.com` | Must be valid |
| `CACHE_STORE` | `database` | Or `redis` for better performance |
| `SESSION_DRIVER` | `database` | Or `redis` |
| `SESSION_SECURE_COOKIE` | `true` | Required for HTTPS |
| `LOG_CHANNEL` | `daily` | Rotated daily logs |

## Web Server Configuration

### Document Root

The document root **must** be the `/public` directory, not the project root.

**Apache:**
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /path/to/project/public

    <Directory /path/to/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /path/to/project/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Deployment Steps

### 1. Pull Latest Code
```bash
cd /path/to/project
git pull origin main
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 3. Environment
```bash
cp .env.example .env
# Edit .env with production values
php artisan key:generate   # only if APP_KEY is empty
```

### 4. Storage Link
```bash
php artisan storage:link
```

### 5. File Permissions
```bash
# Web server user (www-data, nginx, etc.)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/logs
```

### 6. Database Migrations
```bash
php artisan migrate --force
```

### 7. Optimize
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Verify Health Check
```bash
curl -s https://yourdomain.com/up
# Expected: {"status":"ok"}
```

## Backup Requirements

### Database Backup
```bash
# Automated via cron or scheduled task
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Media/Uploads Backup
Back up the `storage/app/public` directory (symlinked to `public/storage`).

This includes all gallery photos (stored under the `photos` media collection for each gallery), featured images for pages and any other uploaded media. Ensure the entire `media` table in the database is included in database backups, as Spatie Media Library tracks file metadata there.

### Recommended Frequency
- Database: Daily (minimum), hourly for high-traffic sites
- Media: Weekly (or use cloud storage with versioning)
- Full application: Before each deployment

## Restore Procedure

1. Stop the application (enable maintenance mode):
   ```bash
   php artisan down
   ```
2. Restore database from backup:
   ```bash
   mysql -u username -p database_name < backup_YYYYMMDD.sql
   ```
3. Restore `storage/app/public` from backup
4. Re-enable the application:
   ```bash
   php artisan up
   ```

## Rollback Plan

### Code Rollback
```bash
git log --oneline -5        # find the last working commit
git revert HEAD             # revert last commit
git push origin main        # deploy the revert
```

### Full Rollback
1. `php artisan down`
2. `git checkout <last-known-good-commit>`
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force` (if migrations were added)
5. `php artisan optimize`
6. `php artisan up`

## Security Headers

Configure at the web server level or via middleware:

- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 0` (modern browsers rely on CSP)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy: <your policy>`

## Logging

- Use `daily` log channel in production
- Monitor `storage/logs/laravel.log`
- Set `LOG_LEVEL=warning` or `error` in production
- Consider external log aggregation (Papertrail, CloudWatch, etc.)

## Maintenance Mode

```bash
# Enable
php artisan down

# With custom response
php artisan down --render="errors::503" --retry=60

# Disable
php artisan up
```

## Post-Deploy Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` is set
- [ ] `APP_URL` is HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `LOG_CHANNEL=daily`
- [ ] `MAIL_FROM_ADDRESS` configured
- [ ] Database migrated (`--force`)
- [ ] `storage:link` exists
- [ ] `composer install --no-dev` completed
- [ ] `npm run build` completed
- [ ] `php artisan optimize` ran
- [ ] `/up` health check returns `{"status":"ok"}`
- [ ] File permissions correct on `storage/` and `bootstrap/cache`
- [ ] SSL certificate valid
- [ ] No sensitive data in logs
