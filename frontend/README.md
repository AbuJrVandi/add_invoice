# Frontend (React + Vite)

This frontend is the admin interface for login, invoices, payments, and PDF settings.

## Stack

- Node.js 20+
- React 18
- Vite 5
- Axios
- React Router v6

## Setup

1. Install dependencies:

```bash
npm install
```

2. Create env file:

```bash
cp .env.example .env
```

3. Set backend URLs in `frontend/.env` (or `frontend/.env.local`):

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_BACKEND_BASE_URL=http://localhost:8000
```

4. Run dev server:

```bash
npm run dev
```

Default dev URL: `http://localhost:5173`

## Authentication Flow

- Login calls `POST /api/login`
- API token is stored in `localStorage` key `ims_token`
- User profile is stored in `localStorage` key `ims_user`
- Axios automatically sends `Authorization: Bearer <token>`
- On 401, frontend clears session and redirects to `/login`

## Main Pages

- `/login` - Admin authentication
- `/` - Dashboard metrics and quick actions
- `/invoices` - Search and review invoices
- `/invoices/create` - Create invoice with preview
- `/invoices/:invoiceId/view` - PDF preview/download
- `/payments` - Payment history and new payment flow
- `/pdf-settings` - Company/PDF branding settings

## Build for Production

```bash
npm run build
npm run preview
```
