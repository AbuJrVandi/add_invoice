# Invoice Management System

Full-stack invoicing system for CIRQON Electronics.

- Backend: Laravel 12 API + Sanctum + SQLite + DOMPDF
- Frontend: React (Vite) + Axios + React Router

## Who This Is For

- Beginners who want to run and use the app locally
- Developers who need architecture and API details before making changes

## Documentation Map

- User manual: `docs/USER_MANUAL.md`
- Technical documentation: `docs/TECHNICAL_DOCUMENTATION.md`
- Backend setup quick reference: `backend/README.md`
- Frontend setup quick reference: `frontend/README.md`

## Quick Start (Local, SQLite)

1. Backend setup:

```bash
cd backend
composer install
cp .env.example .env
touch database/database.sqlite
```

2. Set local SQLite values in `backend/.env`:

```env
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/backend/database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

3. Initialize backend:

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

4. Frontend setup:

```bash
cd frontend
npm install
cp .env.example .env
```

5. Set frontend API URLs in `frontend/.env` (or `frontend/.env.local`):

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_BACKEND_BASE_URL=http://localhost:8000
```

6. Start frontend:

```bash
npm run dev
```

7. Open app in browser:

- Frontend: `http://localhost:5173`

## Default Login (Seeded)

- Email: `admin@invoicesystem.com`
- Password: `password`

Change these credentials before any production deployment.
