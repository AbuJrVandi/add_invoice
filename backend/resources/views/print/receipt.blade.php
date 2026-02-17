<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $receipt_number }}</title>
    <style>
        /* ===== Thermal Receipt — 80mm width ===== */
        @page {
            margin: 2mm;
            size: 80mm auto;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 76mm;
            margin: 0 auto;
            padding: 4mm 2mm;
            line-height: 1.5;
        }

        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: 700; }
        .small  { font-size: 9px; }

        .logo-wrap {
            text-align: center;
            margin-bottom: 4px;
        }

        .logo-wrap img {
            max-width: 50mm;
            max-height: 20mm;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
            margin: 2px 0;
        }

        .company-detail {
            text-align: center;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
        }

        hr.dashed {
            border: none;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        hr.double {
            border: none;
            border-top: 2px double #000;
            margin: 6px 0;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 4px 0;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            padding: 1px 0;
        }

        .meta-label {
            font-weight: 700;
            color: #333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }

        .items-table th {
            font-size: 10px;
            font-weight: 700;
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 3px 0;
        }

        .items-table th.num {
            text-align: right;
        }

        .items-table td {
            font-size: 10px;
            padding: 3px 0;
            vertical-align: top;
        }

        .items-table td.num {
            text-align: right;
            white-space: nowrap;
        }

        .items-table .item-desc {
            max-width: 38mm;
            word-wrap: break-word;
        }

        .totals-block {
            margin: 4px 0;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 2px 0;
        }

        .totals-row.grand {
            font-size: 14px;
            font-weight: 700;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin: 2px 0;
        }

        .payment-info {
            margin: 4px 0;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            padding: 1px 0;
        }

        .footer-msg {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            margin: 8px 0 4px 0;
        }

        .footer-sub {
            text-align: center;
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        @media print {
            body {
                width: 76mm;
                padding: 2mm;
            }
        }
    </style>
</head>
<body>

    <!-- LOGO -->
    <div class="logo-wrap">
        @if(!empty($company['logo']))
            <img src="{{ $company['logo'] }}" alt="Logo">
        @endif
    </div>

    <!-- COMPANY INFO -->
    <div class="company-name">{{ $company['name'] ?? 'CIRQON Electronics' }}</div>
    <div class="company-detail">
        {{ $company['address'] ?? 'No. 4 Light-Foot Boston Street' }}<br>
        {{ $company['phone'] ?? '+232 74 141141 | +232 79 576950' }}<br>
        {{ $company['email'] ?? 'Jamesericksoncole57@gmail.com' }}
    </div>

    <hr class="double">

    <!-- RECEIPT TITLE -->
    <div class="receipt-title">RECEIPT</div>

    <hr class="dashed">

    <!-- META -->
    <div class="meta-row">
        <span class="meta-label">Receipt #:</span>
        <span>{{ $receipt_number }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Invoice #:</span>
        <span>{{ $invoice->invoice_number }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Customer:</span>
        <span>{{ $invoice->customer_name }}</span>
    </div>
    @if(!empty($invoice->organization))
    <div class="meta-row">
        <span class="meta-label">Org:</span>
        <span>{{ $invoice->organization }}</span>
    </div>
    @endif
    <div class="meta-row">
        <span class="meta-label">Date:</span>
        <span>{{ $paid_at_display }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Method:</span>
        <span>{{ ucfirst(str_replace('_', ' ', $payment_method)) }}</span>
    </div>

    <hr class="dashed">

    <!-- ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td class="item-desc">{{ $item->description }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ number_format((float)$item->unit_price, 2) }}</td>
                <td class="num">{{ number_format((float)$item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="dashed">

    <!-- TOTALS -->
    <div class="totals-block">
        <div class="totals-row">
            <span>Invoice Total:</span>
            <span class="bold">NLe {{ number_format((float)$invoice->total, 2) }}</span>
        </div>
        <div class="totals-row grand">
            <span>PAID:</span>
            <span>NLe {{ number_format((float)$amount_paid, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Total Paid:</span>
            <span>NLe {{ number_format((float)$invoice->amount_paid, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Balance:</span>
            <span class="bold">NLe {{ number_format((float)$invoice->balance_remaining, 2) }}</span>
        </div>
    </div>

    <div class="totals-row">
        <span>Status:</span>
        <span class="bold">{{ strtoupper($invoice->status) }}</span>
    </div>

    <hr class="double">

    <!-- FOOTER -->
    <div class="footer-msg">Thank you for your business!</div>
    <div class="footer-sub">
        Goods sold are not refundable.<br>
        Keep this receipt for your records.
    </div>

    <hr class="dashed">

    <div class="center small" style="margin-top:4px;">
        Powered by CIRQON Electronics
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
