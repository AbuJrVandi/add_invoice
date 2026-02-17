# User Manual

This guide is for staff who use the system to create invoices, track payments, and print receipts.

## 1. What This System Does

You can:

- sign in as an admin
- create invoices and generate PDF files
- view invoice history and balances
- record partial or full payments
- print payment receipts
- update invoice PDF branding (company name, logo, signature)

## 2. Before You Start

Make sure:

- backend is running at `http://localhost:8000`
- frontend is running at `http://localhost:5173`
- your account exists

Default seeded account:

- Email: `admin@invoicesystem.com`
- Password: `password`

## 3. Sign In

1. Open `http://localhost:5173/login`
2. Enter email and password
3. Click `Sign In`

If login fails, check:

- backend is running
- credentials are correct
- database was seeded

## 4. Dashboard Workflow

After login, the dashboard shows:

- total paid revenue
- outstanding amount and unpaid invoice count
- today sales
- quick actions for creating invoice or recording payment
- recent invoices list

Recommended daily routine:

1. Check `Outstanding Amount`
2. Open `Payments` for collections
3. Create new invoices for new transactions

## 5. Create a New Invoice

1. Go to `Create Invoice`
2. Fill required fields:
   - customer name
   - organization
   - bill-to and ship-to
   - invoice and due date
   - one or more items
3. Add all invoice items with quantity and unit price
4. Click `Review Invoice`
5. Confirm details on review screen
6. Click `Create Invoice`

What happens next:

- invoice is saved
- status starts as `pending`
- PDF is generated automatically
- PDF download starts automatically

## 6. View and Search Invoices

1. Go to `Invoices`
2. Use filters:
   - invoice number
   - customer name
   - organization
   - invoice date
3. Click `Search`
4. Open PDF from the action column

Invoice status meaning:

- `pending`: no payment yet
- `due`: partially paid
- `completed`: fully paid

## 7. Record Payments

1. Go to `Payments`
2. Use tabs:
   - `Payment History`
   - `New Payment`
3. In `New Payment`, search invoice by customer or invoice number
4. Click `Pay Now`
5. Enter amount, method, optional notes
6. Click `Confirm Payment`

Payment rules:

- partial payment keeps invoice open (`due`)
- final payment closes invoice (`completed`)
- overpayment is capped to remaining balance

After successful payment:

- payment is saved
- invoice balance updates
- receipt print is triggered

## 8. Print or Reprint Receipt

From `Payment History`:

1. Find a payment row
2. Click the print icon
3. Browser print dialog opens

## 9. Update PDF Settings

1. Go to `PDF Settings`
2. Update:
   - company name
   - issuer name
   - logo image
   - signature image
3. Click `Save PDF Settings`

New invoices will use the updated branding.

## 10. Sign Out

Use the `Sign Out` button in sidebar.

## 11. Common Problems

- Session expired:
Log in again.

- Cannot see PDF or logo:
Ask technical admin to run `php artisan storage:link`.

- Payment not saved:
Check internet/backend availability and verify invoice still has remaining balance.
