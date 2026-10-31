# SOGRATE

BBQ & Beef Jerky.

Texas Made. Seriously Good Jerky.

SOGRATE is a Laravel 13 application built on the retained Safe and Sound Kickboxing CMS foundation. Milestone 01 establishes the independent project, application identity, environment configuration, and a verified baseline. Commerce, blog, and customer account features are not part of this milestone.

## Technology Stack

- **Laravel 13** - PHP framework
- **Blade** - Server-rendered templates
- **Bootstrap 5.3** - Frontend CSS framework (installed via npm)
- **Vite** - Asset bundling
- **MySQL/MariaDB** - Production database target
- **SQLite** - Testing database (in-memory)

## Local Development Setup

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 18+
- npm 9+
- MySQL 8.0+ or MariaDB 10.6+ (for local development)

### Installation

1. Clone the repository and navigate to the project directory.

2. Install Composer dependencies:
   ```bash
   composer install
   ```

3. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate a project-specific application key:
   ```bash
   php artisan key:generate
   ```

5. Configure the independent database in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sogratejerky
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Create the database, run migrations, and seed the baseline:
   ```bash
   php artisan migrate --seed
   ```

7. Install npm dependencies:
   ```bash
   npm install
   ```

8. Start the development build:
   ```bash
   npm run dev
   ```

9. In a separate terminal, start the development server:
   ```bash
   php artisan serve
   ```

10. Visit `http://localhost:8000` in your browser.

### Running Tests

```bash
php artisan test
```

### Code Formatting

```bash
./vendor/bin/pint
```

Check formatting without modifying files:
```bash
./vendor/bin/pint --test
```

### Dependency Audits

```bash
composer audit
npm audit
```

## Production Configuration

Use `.env.production.example` as the deployment template. Supply deployment-specific database, mail, domain, and application-key values outside Git. Do not commit `.env` files or credentials.

`docs/launch-readiness.md` is the current Go/No-Go checklist. `docs/production-readiness.md` contains the deployment, backup, restore, queue, scheduler, and rollback procedure.

## Development Principles

- **Laravel conventions** - Follows standard Laravel directory structure and patterns
- **Server-rendered Blade** - No JavaScript frameworks; Blade templates for all views
- **Bootstrap 5.3** - CSS framework installed via npm, compiled through Vite
- **Tests required** - Changed behavior includes appropriate tests
- **No secrets in source** - Environment files and credentials are never committed
- **Security by default** - CSRF protection, mass-assignment protection, and framework security middleware are never disabled

## Project Structure

```
app/
    Http/Controllers/     # Application controllers
    Models/               # Eloquent models
resources/
    js/                   # JavaScript entry points
    scss/                 # Sass stylesheets
        abstracts/        # Variables, mixins, functions
        base/             # Base styles, typography
        components/       # Reusable component styles
        layout/           # Header, footer, layout styles
    views/                # Blade templates
        components/       # Header, footer partials
        layouts/          # Base layouts
routes/                   # Route definitions
tests/                    # PHPUnit/Pest tests
    Feature/              # Feature tests
    Unit/                 # Unit tests
```

## Milestones

- [Milestone 01 completion report](docs/milestones/milestone-01-report.md)
- [Milestone 02 removal inventory](docs/milestones/milestone-02-removal-inventory.md)

## License

This project is proprietary software. All rights reserved.
