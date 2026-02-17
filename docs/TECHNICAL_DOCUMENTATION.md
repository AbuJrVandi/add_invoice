# Technical Documentation

This document explains how the system works for developers and maintainers.

## 1. System Overview

This project is a split frontend/backend application:

- `backend/`: Laravel API, authentication, business logic, database, PDF generation
- `frontend/`: React SPA for admin operations

Core domains:

- authentication
- invoices and invoice items
- payments and receipts
- sales snapshots for reporting
- PDF branding/settings

## 2. Technology Stack

Backend:

- PHP 8.2+
- Laravel 12
- Laravel Sanctum (API tokens)
- SQLite for local development
- DOMPDF for invoice PDF generation

Frontend:

- React 18
- React Router 6
- Axios
- Vite 5

## 3. Directory Map

- `backend/app/Http/Controllers`: API controllers
- `backend/app/Http/Requests`: request validation rules
- `backend/app/Models`: Eloquent models
- `backend/database/migrations`: schema definition
- `backend/database/seeders`: initial data
- `backend/resources/views/pdf`: invoice PDF template
- `backend/resources/views/print`: receipt print template
- `frontend/src/pages`: route-level screens
- `frontend/src/components`: reusable UI components
- `frontend/src/context`: auth state and session handling
- `frontend/src/services/api.js`: Axios client and interceptors

## 4. Local Development Workflow

## Backend

```bash
cd backend
composer install
cp .env.example .env
touch database/database.sqlite
```

Update `backend/.env`:

```env
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/backend/database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Initialize and run:

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

## Frontend

```bash
cd frontend
npm install
cp .env.example .env
```

Update `frontend/.env` or `frontend/.env.local`:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_BACKEND_BASE_URL=http://localhost:8000
```

Run:

```bash
npm run dev
```

## 5. Environment Variables

Backend essentials:

- `APP_URL`: backend base URL
- `DB_CONNECTION`, `DB_DATABASE`: database connection and file path for SQLite
- `SESSION_DRIVER`: local value is `file`
- `CACHE_STORE`: local value is `file`
- `QUEUE_CONNECTION`: local value is `sync`

Frontend essentials:

- `VITE_API_BASE_URL`: API base URL used by Axios
- `VITE_BACKEND_BASE_URL`: base URL used for public file links

## 6. Authentication Design

Flow:

1. Frontend posts credentials to `POST /api/login`
2. Backend validates and creates Sanctum token
3. Frontend stores:
   - `ims_token` in localStorage
   - `ims_user` in localStorage
4. Axios interceptor sends `Authorization: Bearer <token>`
5. Protected routes use `auth:sanctum`
6. On `401`, frontend clears local storage and redirects to `/login`

## 7. Data Model

## invoices

Stores invoice header data, totals, payment status, and generated PDF path.

Important fields:

- `invoice_number` unique
- `status` (`pending`, `due`, `completed`)
- `amount_paid`
- `balance_remaining`
- `pdf_path`

## invoice_items

Line items per invoice:

- `invoice_id` FK
- `description`
- `quantity`
- `unit_price`
- `amount`

## payments

Payment events:

- `invoice_id` FK
- `receipt_number` unique
- `amount_paid`
- `payment_method` (`cash`, `transfer`, `mobile_money`, `card`)
- `paid_at`
- `created_by`
- `notes`

## sales

One sale snapshot per fully paid invoice:

- `invoice_id` unique FK
- `payment_id` FK
- `total_cost_price`
- `total_sale_price`
- `profit`

## invoice_pdf_settings

Invoice branding configuration:

- `company_name`
- `issuer_name`
- `logo_path`
- `signature_path`

## 8. Invoice and Payment Lifecycle

Invoice creation:

1. Frontend requests next invoice number
2. Frontend submits invoice payload with items
3. Backend calculates item totals, subtotal, tax, and total
4. Backend creates invoice + items in transaction
5. Backend renders PDF and stores it in public disk
6. Invoice starts with:
   - `status = pending`
   - `amount_paid = 0`
   - `balance_remaining = total`

Payment processing:

1. Frontend submits payment for invoice
2. Backend validates and caps overpayment to remaining balance
3. Backend creates payment record in transaction
4. Backend updates invoice status:
   - partial => `due`
   - full => `completed` and `paid_at` set
5. Backend creates one `sales` row for fully paid invoice (if missing)

## 9. API Reference

Base URL: `/api`

Auth:

- `POST /login` - public
- `POST /logout` - requires token

Dashboard:

- `GET /dashboard` - requires token

Invoices:

- `GET /invoices/next-number` - requires token
- `GET /invoices` - requires token
- `POST /invoices` - requires token
- `GET /invoices/{invoice}` - requires token
- `GET /invoices/{invoice}/pdf` - requires token
- `DELETE /invoices/{invoice}` - requires token

Payments:

- `GET /payments/search-invoices` - requires token
- `GET /payments` - requires token
- `POST /payments` - requires token
- `GET /payments/{payment}` - requires token
- `GET /payments/{payment}/receipt` - signed URL route

PDF settings:

- `GET /pdf-settings` - requires token
- `POST /pdf-settings` - requires token, multipart form-data

## 10. Validation Rules (Highlights)

Invoice create (`StoreInvoiceRequest`):

- customer and organization required
- due date must be on/after invoice date
- at least one item
- item quantity min 1
- item unit price min 0

PDF settings update (`UpdateInvoicePdfSettingRequest`):

- company and issuer names required
- logo/signature optional image files
- allowed: jpg, jpeg, png, webp
- max file size: 3 MB

Payment create (`PaymentController@store`):

- `invoice_id` required and must exist
- `amount_paid` numeric >= 0.01
- method must be one of supported values

## 11. Important Files

- API routes: `backend/routes/api.php`
- Auth context: `frontend/src/context/AuthContext.jsx`
- Axios config: `frontend/src/services/api.js`
- Invoice logic: `backend/app/Http/Controllers/InvoiceController.php`
- Payment logic: `backend/app/Http/Controllers/PaymentController.php`
- Dashboard metrics: `backend/app/Http/Controllers/DashboardController.php`

## 12. Testing and Quality

Backend:

```bash
cd backend
php artisan test
```

Frontend currently has no test setup in this repository.

## 13. Production Notes

- replace default admin credentials
- use strong `APP_KEY`
- move from SQLite to managed DB if needed
- set strict CORS/domain settings
- set secure cookie/token policies
- run with proper web server + process manager
