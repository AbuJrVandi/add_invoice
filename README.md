# Invoice Management System

Production-oriented full-stack scaffold:
- Backend: Laravel API + Sanctum + SQLite + DOMPDF
- Frontend: React (Vite) + Axios + React Router

## Project Structure
- `backend/` Laravel API code
- `frontend/` React UI

## Implemented Features
- Single-admin authentication with Sanctum token login/logout
- Protected frontend routes
- Dashboard with total invoices, total revenue, and 5 recent invoices
- Invoice creation with multiple invoice items
- Automatic PDF generation on invoice create; path persisted in DB
- Invoice filtering by invoice number, customer, organization, date/year/month/day
- Eloquent relationships and foreign key constraints
- Request validation for auth, invoice create, and invoice filters

## Local Run
1. Start backend from `backend/` (see `backend/README.md`)
2. Start frontend from `frontend/` (see `frontend/README.md`)
