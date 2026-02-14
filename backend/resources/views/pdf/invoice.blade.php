<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 14mm 12mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            color: #0f172a;
            font-size: 11.5px;
            line-height: 1.42;
        }
        .page {
            padding: 0;
        }
        .top {
            width: 100%;
            margin-bottom: 18px;
        }
        .top td {
            vertical-align: top;
        }
        .title {
            font-family: DejaVu Serif, serif;
            font-size: 56px;
            line-height: 1;
            letter-spacing: 0.6px;
            margin: 0;
            color: #1e2b59;
            font-weight: 700;
        }
        .logo-circle {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #b8bbc1;
            text-align: center;
            line-height: 88px;
            font-weight: 700;
            font-size: 14px;
            color: #fff;
            margin-left: auto;
        }
        .logo-image {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: auto;
            display: block;
        }
        .company {
            margin-top: 8px;
            line-height: 1.45;
        }
        .company .name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #0b1326;
        }
        .meta {
            width: 100%;
            margin-top: 12px;
            margin-bottom: 14px;
        }
        .meta td {
            width: 33%;
            vertical-align: top;
            padding-right: 12px;
        }
        .section-label {
            font-family: DejaVu Serif, serif;
            color: #1e2b59;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.6px;
            margin: 0 0 6px;
            line-height: 1;
            text-transform: uppercase;
        }
        .meta-block {
            margin: 0;
            line-height: 1.4;
            white-space: pre-wrap;
        }
        .kv {
            width: 100%;
        }
        .kv td {
            padding-bottom: 5px;
            vertical-align: top;
        }
        .kv .k {
            font-family: DejaVu Serif, serif;
            color: #1e2b59;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: 108px;
        }
        .divider {
            border-top: 2px solid #d4534f;
            margin: 12px 0 2px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 8px;
        }
        table.items thead th {
            font-family: DejaVu Serif, serif;
            color: #1e2b59;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-align: left;
            padding: 7px 6px 7px;
            border-bottom: 2px solid #d4534f;
        }
        table.items tbody td {
            padding: 8px 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items tbody tr {
            page-break-inside: avoid;
        }
        .t-right {
            text-align: right;
        }
        .totals {
            width: 350px;
            margin-left: auto;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .totals td {
            padding: 3px 0;
        }
        .totals .label {
            text-align: right;
            padding-right: 12px;
        }
        .totals .value {
            text-align: right;
            width: 140px;
            font-weight: 600;
        }
        .total-row .label,
        .total-row .value {
            font-family: DejaVu Serif, serif;
            color: #1e2b59;
            font-weight: 700;
            font-size: 24px;
            padding-top: 6px;
        }
        .signature-wrap {
            width: 100%;
            margin-top: 12px;
            margin-bottom: 22px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 260px;
            margin-left: auto;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        .signature-box img {
            max-height: 52px;
            max-width: 250px;
        }
        .footer {
            width: 100%;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .footer td {
            vertical-align: top;
        }
        .thank-you {
            font-family: DejaVu Serif, serif;
            color: #1e2b59;
            font-size: 50px;
            font-style: italic;
            font-weight: 700;
            line-height: 1;
        }
        .terms {
            border-left: 2px solid #1e2b59;
            padding-left: 12px;
            width: 300px;
        }
        .terms .head {
            font-family: DejaVu Serif, serif;
            color: #d4534f;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .terms p {
            margin: 0 0 5px;
            line-height: 1.3;
        }
    </style>
</head>
<body>
<div class="page">
    <table class="top">
        <tr>
            <td>
                <h1 class="title">INVOICE</h1>
                <div class="company">
                    <div class="name">{{ $company['name'] }}</div>
                    @foreach($company['address_lines'] as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            </td>
            <td style="width:118px;">
                @if(file_exists($company['logo']))
                    <img class="logo-image" src="{{ $company['logo'] }}" alt="Logo">
                @else
                    <div class="logo-circle">LOGO</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <h2 class="section-label">BILL TO</h2>
                <p class="meta-block">{{ $invoice->bill_to }}</p>
            </td>
            <td>
                <h2 class="section-label">SHIP TO</h2>
                <p class="meta-block">{{ $invoice->ship_to }}</p>
            </td>
            <td>
                <table class="kv">
                    <tr>
                        <td class="k">Invoice #</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="k">Invoice Date</td>
                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="k">P.O.#</td>
                        <td>{{ $invoice->po_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Customer</td>
                        <td>{{ $invoice->customer_name }}</td>
                    </tr>
                    <tr>
                        <td class="k">Organization</td>
                        <td>{{ $invoice->organization }}</td>
                    </tr>
                    <tr>
                        <td class="k">Due Date</td>
                        <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:75px;">QTY</th>
                <th>DESCRIPTION</th>
                <th style="width:120px;" class="t-right">UNIT PRICE</th>
                <th style="width:120px;" class="t-right">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="t-right">NLe {{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="t-right">NLe {{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">NLe {{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Sales Tax</td>
            <td class="value">NLe {{ number_format((float) $invoice->tax, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">TOTAL</td>
            <td class="value">NLe {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
    </table>

    <div class="signature-wrap">
        <div class="signature-box">
            @if(file_exists($company['signature']))
                <img src="{{ $company['signature'] }}" alt="Signature">
            @else
                <div style="font-size:30px;font-style:italic;font-weight:700;color:#1e2b59;">{{ $company['issuer_name'] }}</div>
            @endif
            <div style="margin-top:6px;font-size:13px;font-weight:700;color:#1e2b59;">{{ $company['issuer_name'] }}</div>
        </div>
    </div>

    <table class="footer">
        <tr>
            <td>
                <div class="thank-you">Thank you</div>
            </td>
            <td class="terms">
                <div class="head">Terms & Conditions</div>
                @foreach($company['terms'] as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </td>
        </tr>
    </table>
</div>
</body>
</html>
