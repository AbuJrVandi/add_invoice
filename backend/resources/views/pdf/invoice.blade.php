<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
            size: A4 portrait;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333333;
            font-size: 10px;
            line-height: 1.4;
        }

        .page {
            padding: 0;
        }

        /* ===== HEADER: Logo left, INVOICE title right ===== */
        .header-table {
            width: 100%;
            margin-bottom: 0;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 200px;
        }

        .logo-image {
            max-width: 160px;
            max-height: 90px;
        }

        .company-address {
            margin-top: 4px;
            font-size: 8px;
            color: #555555;
            line-height: 1.4;
        }

        .company-phone {
            font-size: 8px;
            color: #555555;
            margin-top: 2px;
        }

        .invoice-title-cell {
            text-align: right;
            vertical-align: top;
        }

        .invoice-title {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 42px;
            font-weight: 700;
            color: #333333;
            margin: 0;
            letter-spacing: 2px;
            line-height: 1;
        }

        .invoice-number {
            font-size: 14px;
            color: #E8760A;
            margin-top: 6px;
            font-weight: 400;
        }

        /* ===== ORANGE DIVIDER ===== */
        .orange-divider {
            border: none;
            border-top: 3px solid #E8760A;
            margin: 14px 0 16px 0;
        }

        .thin-divider {
            border: none;
            border-top: 1px solid #CCCCCC;
            margin: 6px 0 10px 0;
        }

        /* ===== BILL TO + DETAILS SECTION ===== */
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .meta-table td {
            vertical-align: top;
        }

        .bill-to-cell {
            width: 55%;
        }

        .details-cell {
            width: 45%;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #E8760A;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bill-to-content {
            font-size: 10px;
            color: #333333;
            line-height: 1.6;
            margin-top: 8px;
        }

        .details-table {
            width: 100%;
            margin-top: 8px;
        }

        .details-table td {
            padding: 3px 0;
            font-size: 10px;
            vertical-align: top;
        }

        .details-label {
            font-weight: 700;
            color: #555555;
            width: 100px;
        }

        .details-value {
            color: #333333;
        }

        /* ===== ITEMS TABLE ===== */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        table.items thead th {
            background-color: #E8760A;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
        }

        table.items thead th.t-right {
            text-align: right;
        }

        table.items tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #E5E5E5;
            font-size: 10px;
            color: #333333;
            vertical-align: top;
        }

        table.items tbody td.t-right {
            text-align: right;
        }

        table.items tbody tr {
            page-break-inside: avoid;
        }

        .item-title {
            font-weight: 700;
            font-size: 10px;
            color: #333333;
        }

        .item-subtitle {
            font-size: 9px;
            color: #666666;
            margin-top: 2px;
            line-height: 1.3;
        }

        /* ===== TOTALS ===== */
        .totals-wrapper {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 12px;
            font-size: 10px;
        }

        .totals-table .totals-label {
            text-align: right;
            font-weight: 700;
            color: #555555;
            background-color: #F5F5F5;
            border: 1px solid #E5E5E5;
            width: 120px;
        }

        .totals-table .totals-value {
            text-align: right;
            color: #333333;
            border: 1px solid #E5E5E5;
            width: 160px;
        }

        .totals-table .total-row .totals-label {
            font-size: 12px;
            color: #E8760A;
            font-weight: 700;
        }

        .totals-table .total-row .totals-value {
            font-size: 13px;
            font-weight: 700;
            color: #E8760A;
        }

        /* ===== PAYMENT INSTRUCTIONS ===== */
        .payment-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .payment-divider {
            border: none;
            border-top: 3px solid #E8760A;
            margin: 0 0 4px 0;
        }

        .payment-thin-divider {
            border: none;
            border-top: 1px solid #CCCCCC;
            margin: 0 0 12px 0;
        }

        .payment-title {
            font-size: 14px;
            font-weight: 700;
            color: #E8760A;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-content {
            font-size: 10px;
            color: #333333;
            line-height: 1.8;
        }

        .payment-content .label {
            font-weight: 700;
            color: #555555;
        }

        /* ===== FOOTER ===== */
        .footer-section {
            margin-top: 30px;
            text-align: center;
            page-break-inside: avoid;
        }

        .footer-thanks {
            font-size: 12px;
            font-weight: 700;
            color: #333333;
            margin-bottom: 4px;
        }

        .footer-contact {
            font-size: 9px;
            color: #777777;
            font-style: italic;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<div class="page">

    <!-- HEADER: Logo + Company Info | INVOICE Title -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(file_exists($company['logo']))
                    <img class="logo-image" src="{{ $company['logo'] }}" alt="Logo">
                @else
                    <div style="font-size:24px;font-weight:700;color:#E8760A;">CIRQON</div>
                @endif
                <div class="company-address">
                    {{ $company['address_lines'][0] ?? 'No. 4 Light-Foot Boston Street' }}<br>
                    {{ $company['address_lines'][1] ?? 'Via Radwon Street, Freetown' }}
                </div>
                <div class="company-phone">📞 {{ $company['phone'] ?? '+232 74 141141 | +232 79 576950' }}</div>
            </td>
            <td class="invoice-title-cell">
                <h1 class="invoice-title">INVOICE</h1>
                <div class="invoice-number"># {{ $invoice->invoice_number }}</div>
            </td>
        </tr>
    </table>

    <!-- Orange divider line -->
    <hr class="orange-divider">

    <!-- BILL TO + DETAILS -->
    <table class="meta-table">
        <tr>
            <td class="bill-to-cell">
                <div class="section-title">BILL TO</div>
                <hr class="thin-divider">
                <div class="bill-to-content">
                    {!! nl2br(e($invoice->bill_to)) !!}
                    @if($invoice->ship_to)
                        <br>{{ $invoice->ship_to }}
                    @endif
                </div>
            </td>
            <td class="details-cell">
                <div class="section-title">DETAILS</div>
                <table class="details-table">
                    <tr>
                        <td class="details-label">Date:</td>
                        <td class="details-value">{{ $invoice->invoice_date->format('n/j/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">Customer ID:</td>
                        <td class="details-value">{{ $invoice->po_number ?: $invoice->invoice_number }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td class="details-label">Due Date:</td>
                        <td class="details-value">{{ $invoice->due_date->format('n/j/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="details-label">Organization:</td>
                        <td class="details-value">{{ $invoice->organization }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ITEMS TABLE -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:50%;">DESCRIPTION</th>
                <th style="width:10%;text-align:center;">QTY</th>
                <th style="width:20%;" class="t-right">UNIT PRICE</th>
                <th style="width:20%;" class="t-right">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-title">{{ $item->description }}</div>
                    </td>
                    <td style="text-align:center;">{{ $item->quantity }}</td>
                    <td class="t-right">{{ number_format((float) $item->unit_price, 2) }} LE</td>
                    <td class="t-right">{{ number_format((float) $item->amount, 2) }} LE</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALS -->
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td class="totals-label">SUB TOTAL</td>
                <td class="totals-value">{{ number_format((float) $invoice->subtotal, 2) }} LE</td>
            </tr>
            <tr>
                <td class="totals-label">DISCOUNT</td>
                <td class="totals-value">{{ $invoice->tax > 0 ? number_format((float) $invoice->tax, 2) . ' LE' : '-' }}</td>
            </tr>
            <tr class="total-row">
                <td class="totals-label">TOTAL</td>
                <td class="totals-value">{{ number_format((float) $invoice->total, 2) }} LE</td>
            </tr>
        </table>
    </div>

    <!-- PAYMENT INSTRUCTIONS -->
    <div class="payment-section">
        <hr class="payment-divider">
        <hr class="payment-thin-divider">
        <div class="payment-title">PAYMENT INSTRUCTIONS</div>
        <div class="payment-content">
            Please make payment to:<br>
            <span class="label">Bank:</span> {{ $company['bank'] ?? 'UBA' }}<br>
            <span class="label">Account Name:</span> {{ $company['account_name'] ?? 'Wickburn Services SL LTD' }}<br>
            <span class="label">Account No:</span> {{ $company['account_no'] ?? '5401-1003-000922-9' }}<br>
            <span class="label">IBAN:</span> {{ $company['iban'] ?? '010401100300092257' }}<br>
            <span class="label">BIC/SWIFT CODE:</span> {{ $company['swift_code'] ?? 'UNAFSLFR' }}
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-section">
        <div class="footer-thanks">Thank you for your business!</div>
        <div class="footer-contact">
            If you have any questions about this invoice, please contact<br>
            {{ $company['contact_person'] ?? 'James Cole' }} | {{ $company['phone'] ?? '+232 79576950' }} | {{ $company['contact_email'] ?? 'Jamesericksoncole57@gmail.com' }}
        </div>
    </div>

</div>
</body>
</html>
