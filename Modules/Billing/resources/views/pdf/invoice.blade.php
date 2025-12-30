<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->display_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
        }

        .company-info {
            text-align: left;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 5px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 32px;
            color: #0066cc;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 14px;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .status-draft { background: #e5e5e5; color: #666; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
        .status-void { background: #f3f4f6; color: #6b7280; }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .info-box h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-box p {
            margin-bottom: 3px;
        }

        .dates-section {
            margin-bottom: 30px;
        }

        .dates-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dates-table td {
            padding: 8px 15px;
            border: 1px solid #ddd;
        }

        .dates-table td:first-child {
            background: #f9fafb;
            font-weight: bold;
            width: 30%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background: #0066cc;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table th:nth-child(2),
        .items-table th:nth-child(3),
        .items-table th:nth-child(4),
        .items-table td:nth-child(2),
        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: center;
        }

        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e5e5;
        }

        .items-table tr:nth-child(even) {
            background: #f9fafb;
        }

        .totals-section {
            width: 350px;
            margin-left: auto;
            margin-bottom: 30px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 15px;
            border-bottom: 1px solid #e5e5e5;
        }

        .totals-table td:last-child {
            text-align: right;
            font-weight: 500;
        }

        .totals-table .total-row {
            background: #0066cc;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .totals-table .total-row td {
            border: none;
            padding: 12px 15px;
        }

        .notes-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .notes-section h3 {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }

        .terms-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #0066cc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background: #dcfce7;
            border-radius: 8px;
        }

        .payment-info h3 {
            color: #166534;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table style="width: 100%; margin-bottom: 40px; border-bottom: 2px solid #0066cc; padding-bottom: 20px;">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">{{ config('app.name', 'Siliconile') }}</div>
                <div style="color: #666; font-size: 11px;">
                    {{ config('billing.company_address', 'Coworking Space') }}<br>
                    {{ config('billing.company_phone', '') }}<br>
                    {{ config('billing.company_email', '') }}
                </div>
                @if(config('billing.company_vat_number'))
                    <div style="margin-top: 5px; font-size: 10px;">
                        VAT: {{ config('billing.company_vat_number') }}
                    </div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: top;">
                <h1 style="font-size: 32px; color: #0066cc; margin-bottom: 10px;">INVOICE</h1>
                <div style="font-size: 14px; color: #666;">
                    {{ $invoice->display_number }}
                </div>
                <div class="status-badge status-{{ $invoice->status->value }}">
                    {{ $invoice->status->getLabel() }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Bill To & Dates -->
    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 30px;">
                <h3 style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Bill To</h3>
                <p style="font-weight: bold; font-size: 14px;">{{ $invoice->billing_details['name'] ?? $invoice->billable_name }}</p>
                @if($invoice->billing_details['company_name'] ?? null)
                    <p>{{ $invoice->billing_details['company_name'] }}</p>
                @endif
                @if($invoice->billing_details['email'] ?? $invoice->billable_email)
                    <p>{{ $invoice->billing_details['email'] ?? $invoice->billable_email }}</p>
                @endif
                @if($invoice->billing_details['company_address'] ?? null)
                    <p>{{ $invoice->billing_details['company_address'] }}</p>
                @endif
                @if($invoice->billing_details['company_vat_number'] ?? null)
                    <p>VAT: {{ $invoice->billing_details['company_vat_number'] }}</p>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 15px; background: #f9fafb; font-weight: bold; border: 1px solid #ddd;">Issue Date</td>
                        <td style="padding: 8px 15px; border: 1px solid #ddd;">{{ $invoice->issue_date?->format('F j, Y') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; background: #f9fafb; font-weight: bold; border: 1px solid #ddd;">Due Date</td>
                        <td style="padding: 8px 15px; border: 1px solid #ddd; {{ $invoice->isOverdue() ? 'color: #dc2626; font-weight: bold;' : '' }}">
                            {{ $invoice->due_date?->format('F j, Y') ?? 'N/A' }}
                            @if($invoice->isOverdue())
                                (OVERDUE)
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; background: #f9fafb; font-weight: bold; border: 1px solid #ddd;">Currency</td>
                        <td style="padding: 8px 15px; border: 1px solid #ddd;">{{ $invoice->currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Line Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Description</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 15%;">Discount</th>
                <th style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ number_format((float)$item->unit_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format((float)$item->discount_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format((float)$item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table style="width: 350px; margin-left: auto; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 15px; border-bottom: 1px solid #e5e5e5;">Subtotal</td>
            <td style="padding: 8px 15px; text-align: right; border-bottom: 1px solid #e5e5e5;">{{ $invoice->currency }} {{ number_format((float)$invoice->subtotal, 2) }}</td>
        </tr>
        @if((float)$invoice->discount_amount > 0)
            <tr>
                <td style="padding: 8px 15px; border-bottom: 1px solid #e5e5e5;">
                    Discount
                    @if($invoice->discount_description)
                        <br><small style="color: #666;">{{ $invoice->discount_description }}</small>
                    @endif
                </td>
                <td style="padding: 8px 15px; text-align: right; color: #dc2626; border-bottom: 1px solid #e5e5e5;">-{{ $invoice->currency }} {{ number_format((float)$invoice->discount_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 8px 15px; border-bottom: 1px solid #e5e5e5;">VAT ({{ $invoice->tax_rate }}%)</td>
            <td style="padding: 8px 15px; text-align: right; border-bottom: 1px solid #e5e5e5;">{{ $invoice->currency }} {{ number_format((float)$invoice->tax_amount, 2) }}</td>
        </tr>
        <tr style="background: #0066cc; color: white; font-weight: bold;">
            <td style="padding: 12px 15px;">Total</td>
            <td style="padding: 12px 15px; text-align: right; font-size: 16px;">{{ $invoice->currency }} {{ number_format((float)$invoice->total, 2) }}</td>
        </tr>
    </table>

    <!-- Payment Info (if paid) -->
    @if($invoice->status === \Modules\Billing\Enums\InvoiceStatus::PAID)
        <div class="payment-info">
            <h3>Payment Received</h3>
            <p><strong>Date:</strong> {{ $invoice->paid_at?->format('F j, Y H:i') }}</p>
            @if($invoice->payment_reference)
                <p><strong>Reference:</strong> {{ $invoice->payment_reference }}</p>
            @endif
            @if($invoice->payment_method)
                <p><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->payment_method)) }}</p>
            @endif
        </div>
    @endif

    <!-- Notes -->
    @if($invoice->notes)
        <div class="notes-section">
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    <!-- Terms -->
    @if($invoice->terms)
        <div class="terms-section">
            <h3 style="margin-bottom: 10px;">Terms & Conditions</h3>
            <p>{{ $invoice->terms }}</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your business!</p>
        <p style="margin-top: 5px;">{{ config('app.name', 'Siliconile') }} | {{ config('app.url') }}</p>
    </div>
</body>
</html>
