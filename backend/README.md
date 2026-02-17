# Backend (Laravel API)

This backend powers authentication, invoice management, PDF generation, payments, and dashboard metrics.

## Stack

- PHP 8.2+
- Laravel 12
- SQLite (local development)
- Laravel Sanctum (token auth)
- barryvdh/laravel-dompdf (invoice PDF generation)

## Setup

1. Install dependencies:

```bash
composer install
```

2. Create env file:

```bash
cp .env.example .env
```

3. Create SQLite database file:

```bash
touch database/database.sqlite
```

4. Configure `backend/.env` for local development:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/backend/database/database.sqlite

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

5. Initialize app:

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```

6. Run local API server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## Seeded Admin User

- Email: `admin@invoicesystem.com`
- Password: `password`

Seeder: `database/seeders/DatabaseSeeder.php`

## API Entry Points

Base URL: `http://localhost:8000/api`

- `POST /login`
- `POST /logout`
- `GET /dashboard`
- `GET /invoices`
- `POST /invoices`
- `GET /invoices/{invoice}`
- `GET /invoices/{invoice}/pdf`
- `DELETE /invoices/{invoice}`
- `GET /payments`
- `POST /payments`
- `GET /payments/search-invoices`
- `GET /payments/{payment}`
- `GET /payments/{payment}/receipt` (signed URL)
- `GET /pdf-settings`
- `POST /pdf-settings`

## Common Commands

```bash
# Run migrations + seeders
php artisan migrate --seed

# Roll back one migration batch
php artisan migrate:rollback

# Reset DB and reseed (destructive)
php artisan migrate:fresh --seed

# Run tests
php artisan test
```

## Troubleshooting

- `php: command not found`
Install PHP 8.2+ and ensure it is on your PATH.

- PDF images not visible
Run `php artisan storage:link` and ensure uploaded files exist in `storage/app/public`.

- 401 Unauthorized from frontend
Confirm `VITE_API_BASE_URL` points to local backend and user is logged in.
