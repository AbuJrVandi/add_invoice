@php
    $formatMoney = static function ($value): string {
        $formatted = number_format((float) $value, 2, '.', ',');
        return rtrim(rtrim($formatted, '0'), '.');
    };

    $splitLines = static function (?string $value): array {
        $parts = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
        $clean = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return $clean;
    };

    $billLines = $splitLines($invoice->bill_to);
    $paymentLines = $splitLines($company['payment_instructions'] ?? '');
    $addressLines = $company['address_lines'] ?? [];
    if (! is_array($addressLines)) {
        $addressLines = [trim((string) $addressLines)];
    }

    $terms = $company['terms'] ?? [];
    if (! is_array($terms) || $terms === []) {
        $terms = [
            'Payment is due within 15 days from invoice date.',
            'Goods once delivered are not returnable.',
            'A late fee may apply on overdue balances.',
        ];
    }

    $subtotal = (float) ($invoice->subtotal ?? 0);
    $discountTotal = max((float) ($invoice->tax ?? 0), 0.0);
    $total = (float) ($invoice->total ?? 0);
    $amountPaid = (float) ($invoice->amount_paid ?? 0);
    $balanceDue = (float) ($invoice->balance_remaining ?? max($total - $amountPaid, 0));

    $lineItems = [];
    foreach ($invoice->items as $item) {
        $lineItems[] = [
            'item' => $item,
            'base_amount' => (float) $item->amount,
        ];
    }

    $issuedDate = $invoice->invoice_date?->format('jS F Y')
        ?? $invoice->created_at?->format('jS F Y')
        ?? now()->format('jS F Y');

    $readLabeledBillValue = static function (array $lines, string $label): string {
        foreach ($lines as $line) {
            if (preg_match('/^\s*'.preg_quote($label, '/').'\s*:\s*(.+)$/i', $line, $matches) === 1) {
                return trim((string) ($matches[1] ?? ''));
            }
        }

        return '';
    };

    $billOrg = trim((string) ($invoice->organization ?? ''));
    if ($billOrg === '') {
        $billOrg = $readLabeledBillValue($billLines, 'ORG');
    }

    $billAddress = $readLabeledBillValue($billLines, 'ADDRESS');
    if ($billAddress === '') {
        $billAddress = $billLines[0] ?? '';
    }

    $billPhone = $readLabeledBillValue($billLines, 'PHONE');
    if ($billPhone === '') {
        $billPhone = $billLines[1] ?? '';
    }

    $billEmail = $readLabeledBillValue($billLines, 'EMAIL');
    if ($billEmail === '') {
        $billEmail = $billLines[2] ?? '';
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2945;
            font-size: 11px;
            line-height: 1.4;
            background: #edf0f6;
        }

        .page {
            background: #f0f2f7;
            border: 1px solid #d9deea;
            border-radius: 12px;
            padding: 15px;
        }

        .brand-head {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .brand-head td {
            vertical-align: top;
        }

        .brand-left {
            width: 62%;
        }

        .brand-right {
            width: 38%;
            text-align: right;
        }

        .brand-box {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-box td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 148px;
            vertical-align: top;
        }

        .logo-wrap {
            width: 132px;
            height: 96px;
            border-radius: 8px;
            background: #df7411;
            text-align: center;
            line-height: 96px;
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
        }

        .logo-img {
            width: 132px;
            height: 96px;
            object-fit: contain;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #dbe2ef;
        }

        .brand-name {
            font-size: 28px;
            line-height: 1.04;
            font-weight: 800;
            color: #de730f;
            letter-spacing: 0.4px;
            margin: 0;
            text-transform: uppercase;
        }

        .invoice-title {
            font-size: 64px;
            font-weight: 800;
            color: #de730f;
            line-height: 0.95;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .divider {
            border: none;
            border-top: 2px solid #2d3f6e;
            margin: 6px 0 10px;
        }

        .top-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .top-grid td {
            vertical-align: top;
        }

        .company-info {
            width: 57%;
            padding-right: 12px;
        }

        .invoice-meta {
            width: 43%;
        }

        .info-line {
            margin: 0 0 4px;
            color: #26324f;
            font-size: 11px;
        }

        .info-label {
            color: #5c6b8e;
            font-weight: 700;
            display: inline-block;
            min-width: 74px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 0;
            font-size: 10px;
            vertical-align: top;
        }

        .meta-key {
            width: 94px;
            font-weight: 700;
            color: #1f2945;
        }

        .meta-value {
            color: #1f2945;
        }

        .addr-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 8px;
        }

        .addr-grid td {
            width: 100%;
            vertical-align: top;
        }

        .addr-card {
            border: 1px solid #ced7e8;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }

        .addr-head {
            background: #de730f;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 7px 10px;
        }

        .addr-body {
            padding: 8px 10px;
        }

        .addr-row {
            border-bottom: 1px solid #e3e8f2;
            padding: 5px 0;
        }

        .addr-row:last-child {
            border-bottom: none;
        }

        .addr-k {
            display: inline-block;
            width: 64px;
            font-size: 10px;
            font-weight: 700;
            color: #5f6f92;
            text-transform: uppercase;
        }

        .addr-v {
            font-size: 10px;
            color: #212f50;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #ced6e7;
            border-radius: 10px;
            overflow: hidden;
        }

        .items thead th {
            background: #de730f;
            color: #ffffff;
            padding: 7px 7px;
            font-size: 9.6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
        }

        .items thead th:last-child {
            border-right: none;
        }

        .items tbody td {
            border-bottom: 1px solid #e1e7f2;
            border-right: 1px solid #e1e7f2;
            padding: 7px 8px;
            font-size: 10px;
            color: #202d4e;
            vertical-align: top;
        }

        .items tbody tr:last-child td {
            border-bottom: none;
        }

        .items tbody td:last-child {
            border-right: none;
        }

        .t-center {
            text-align: center;
        }

        .t-right {
            text-align: right;
        }

        .item-name {
            font-size: 11px;
            font-weight: 700;
            color: #1f2e50;
            margin: 0;
        }

        .summary-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px;
            page-break-inside: avoid;
        }

        .summary-grid td {
            vertical-align: top;
        }

        .left-panel {
            width: 56%;
        }

        .right-panel {
            width: 44%;
        }

        .panel-card {
            border: 1px solid #ced7e8;
            border-radius: 10px;
            background: #ffffff;
            padding: 10px;
            margin-bottom: 8px;
        }

        .panel-title {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 800;
            color: #223a66;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #dde4f0;
            padding-bottom: 4px;
        }

        .bullet-list {
            margin: 0;
            padding: 0 0 0 10px;
            list-style: none;
        }

        .bullet-list li {
            position: relative;
            margin-bottom: 5px;
            padding-left: 18px;
            font-size: 10px;
            color: #2a3658;
            line-height: 1.45;
        }

        .bullet-list li:last-child {
            margin-bottom: 0;
        }

        .bullet-list li::before {
            content: '';
            position: absolute;
            left: 2px;
            top: 5px;
            width: 4px;
            height: 4px;
            border-radius: 999px;
            background: #de730f;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            border-bottom: 1px solid #e2e8f3;
            padding: 6px 0;
            font-size: 10px;
        }

        .totals-label {
            color: #33446e;
            font-weight: 700;
        }

        .totals-value {
            text-align: right;
            color: #1d2c4d;
            font-weight: 700;
        }

        .grand-total td {
            font-size: 11px;
            font-weight: 800;
            color: #1a2c56;
        }

        .balance-row td {
            border-bottom: none;
            padding-top: 8px;
        }

        .balance-box {
            width: 100%;
            border-collapse: collapse;
            background: #de730f;
            color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        .balance-box td {
            padding: 8px 10px;
            font-size: 11px;
            font-weight: 800;
            border: none;
        }

        .balance-box .v {
            text-align: right;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sign-table td {
            width: 100%;
            padding: 0;
            text-align: center;
        }

        .sign-title {
            margin: 0;
            font-size: 11px;
            font-weight: 800;
            color: #223a66;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: center;
        }

        .supplier-auth-wrap {
            min-height: 126px;
            margin: 10px 0 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .auth-sign {
            width: 100%;
            max-width: 220px;
            max-height: 90px;
            object-fit: contain;
            object-position: center center;
            display: block;
            margin: 0 auto;
        }

        .sign-placeholder {
            font-size: 10px;
            color: #6a7a9f;
        }

        .sign-meta {
            margin: 0;
            font-size: 10px;
            color: #3a4d76;
            text-align: center;
            font-weight: 700;
        }

        .thanks {
            text-align: center;
            margin-top: 10px;
            font-size: 16px;
            font-weight: 800;
            color: #de730f;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .foot-note {
            text-align: center;
            margin-top: 4px;
            font-size: 9px;
            color: #6a7a9f;
        }
    </style>
</head>
<body>
<div class="page">
    <table class="brand-head">
        <tr>
            <td class="brand-left">
                <table class="brand-box">
                    <tr>
                        <td class="logo-cell">
                            @if(! empty($company['logo']) && file_exists($company['logo']))
                                <img src="{{ $company['logo'] }}" alt="Logo" class="logo-img">
                            @else
                                <div class="logo-wrap">DL</div>
                            @endif
                        </td>
                        <td>
                            <p class="brand-name">{{ $company['name'] ?? 'DIGITAL ELECTRONICS LTD.' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="brand-right">
                <p class="invoice-title">INVOICE</p>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <table class="top-grid">
        <tr>
            <td class="company-info">
                <p class="info-line"><span class="info-label">Address:</span>{{ trim(implode(', ', array_filter($addressLines)), ', ') ?: 'N/A' }}</p>
                <p class="info-line"><span class="info-label">Phone:</span>{{ $company['phone'] ?? 'N/A' }}</p>
                <p class="info-line"><span class="info-label">Email:</span>{{ $company['contact_email'] ?? 'N/A' }}</p>
            </td>
            <td class="invoice-meta">
                <table class="meta-table">
                    <tr>
                        <td class="meta-key">Invoice #:</td>
                        <td class="meta-value">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-key">Invoice Date:</td>
                        <td class="meta-value">{{ $invoice->invoice_date?->format('jS F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-key">Due Date:</td>
                        <td class="meta-value">{{ $invoice->due_date?->format('jS F Y') ?? 'N/A' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="addr-grid">
        <tr>
            <td>
                <div class="addr-card">
                    <div class="addr-head">Bill To</div>
                    <div class="addr-body">
                        <div class="addr-row"><span class="addr-k">ORG:</span><span class="addr-v">{{ $billOrg ?: 'N/A' }}</span></div>
                        <div class="addr-row"><span class="addr-k">ATTN:</span><span class="addr-v">{{ $invoice->customer_name ?: 'N/A' }}</span></div>
                        <div class="addr-row"><span class="addr-k">Address:</span><span class="addr-v">{{ $billAddress ?: 'N/A' }}</span></div>
                        <div class="addr-row"><span class="addr-k">Phone:</span><span class="addr-v">{{ $billPhone ?: ($company['phone'] ?? 'N/A') }}</span></div>
                        <div class="addr-row"><span class="addr-k">Email:</span><span class="addr-v">{{ $billEmail ?: ($company['contact_email'] ?? 'N/A') }}</span></div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:54%;text-align:left;">Item Description</th>
                <th style="width:6%;" class="t-center">Qty</th>
                <th style="width:16%;" class="t-right">Unit Price</th>
                <th style="width:20%;" class="t-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lineItems as $index => $row)
                <tr>
                    <td class="t-center">{{ $index + 1 }}</td>
                    <td>
                        <p class="item-name">{{ $row['item']->description }}</p>
                    </td>
                    <td class="t-center">{{ $row['item']->quantity }}</td>
                    <td class="t-right">{{ $formatMoney($row['item']->unit_price) }}</td>
                    <td class="t-right">{{ $formatMoney($row['base_amount']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="t-center">No invoice items available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-grid">
        <tr>
            <td class="left-panel">
                <div class="panel-card">
                    <h4 class="panel-title">Payment Information</h4>
                    @forelse($paymentLines as $line)
                        @if($loop->first)
                            <ul class="bullet-list">
                        @endif
                        <li>{{ $line }}</li>
                        @if($loop->last)
                            </ul>
                        @endif
                    @empty
                        <ul class="bullet-list">
                            <li>No payment instructions configured.</li>
                        </ul>
                    @endforelse
                </div>

                <div class="panel-card" style="margin-bottom:0;">
                    <h4 class="panel-title">Terms &amp; Conditions</h4>
                    <ul class="bullet-list">
                        @foreach($terms as $term)
                            <li>{{ $term }}</li>
                        @endforeach
                    </ul>
                </div>
            </td>

            <td class="right-panel">
                <div class="panel-card">
                    <table class="totals-table">
                        <tr>
                            <td class="totals-label">Subtotal</td>
                            <td class="totals-value">{{ $formatMoney($subtotal) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Discount</td>
                            <td class="totals-value">-{{ $formatMoney($discountTotal) }}</td>
                        </tr>
                        <tr class="grand-total">
                            <td class="totals-label">Grand Total</td>
                            <td class="totals-value">{{ $formatMoney($total) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Amount Paid</td>
                            <td class="totals-value">{{ $formatMoney($amountPaid) }}</td>
                        </tr>
                        <tr class="balance-row">
                            <td colspan="2">
                                <table class="balance-box">
                                    <tr>
                                        <td>Balance Due</td>
                                        <td class="v">{{ $formatMoney($balanceDue) }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="panel-card" style="margin-bottom:0;">
                    <table class="sign-table">
                        <tr>
                            <td>
                                <div class="sign-title">Supplier Authorization</div>
                                <div class="supplier-auth-wrap">
                                    @if(! empty($company['signature']) && file_exists($company['signature']))
                                        <img src="{{ $company['signature'] }}" alt="Supplier Signature" class="auth-sign">
                                    @else
                                        <div class="sign-placeholder">No signature uploaded.</div>
                                    @endif
                                </div>
                                <p class="sign-meta">Issued Date: {{ $issuedDate }}</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="thanks">Thank You For Your Business!</div>
    <div class="foot-note">For questions, contact {{ $company['contact_person'] ?? 'Accounts Team' }} at {{ $company['contact_email'] ?? 'N/A' }}.</div>
</div>
</body>
</html>
