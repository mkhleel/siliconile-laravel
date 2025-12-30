<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->display_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0066cc;
        }
        .header h1 {
            color: #0066cc;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 18px;
            color: #666;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
        .content {
            margin-bottom: 30px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .summary-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .summary-table td:last-child {
            text-align: right;
            font-weight: 500;
        }
        .total-row {
            background: #f9fafb;
            font-size: 18px;
            font-weight: bold;
        }
        .total-row td {
            padding: 15px 12px;
            border-bottom: none;
        }
        .cta-button {
            display: inline-block;
            background: #0066cc;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
        }
        .cta-button:hover {
            background: #0052a3;
        }
        .message-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'Siliconile') }}</h1>
        <div class="invoice-number">Invoice {{ $invoice->display_number }}</div>
        <div class="status status-{{ $invoice->status->value }}">
            {{ $invoice->status->getLabel() }}
        </div>
    </div>

    <div class="content">
        <p>Dear {{ $invoice->billable_name }},</p>

        @if($invoice->status->value === 'paid')
            <p>Thank you for your payment! This email confirms that invoice <strong>{{ $invoice->number }}</strong> has been paid in full.</p>
        @else
            <p>Please find attached your invoice <strong>{{ $invoice->number }}</strong> for your recent services.</p>
        @endif

        @if($customMessage)
            <div class="message-box">
                <p>{{ $customMessage }}</p>
            </div>
        @endif

        <table class="summary-table">
            <tr>
                <td>Issue Date</td>
                <td>{{ $invoice->issue_date?->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td>Due Date</td>
                <td style="{{ $invoice->isOverdue() ? 'color: #dc2626; font-weight: bold;' : '' }}">
                    {{ $invoice->due_date?->format('F j, Y') }}
                    @if($invoice->isOverdue())
                        (OVERDUE)
                    @endif
                </td>
            </tr>
            <tr>
                <td>Subtotal</td>
                <td>{{ $invoice->currency }} {{ number_format((float)$invoice->subtotal, 2) }}</td>
            </tr>
            @if((float)$invoice->discount_amount > 0)
                <tr>
                    <td>Discount</td>
                    <td style="color: #dc2626;">-{{ $invoice->currency }} {{ number_format((float)$invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td>VAT ({{ $invoice->tax_rate }}%)</td>
                <td>{{ $invoice->currency }} {{ number_format((float)$invoice->tax_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td>{{ $invoice->currency }} {{ number_format((float)$invoice->total, 2) }}</td>
            </tr>
        </table>

        @if($invoice->canBePaid())
            <div style="text-align: center;">
                <a href="{{ route('billing.invoice.pay', $invoice) }}" class="cta-button">
                    Pay Now
                </a>
            </div>
        @endif

        <p>A PDF copy of your invoice is attached to this email for your records.</p>
    </div>

    <div class="footer">
        <p>If you have any questions, please contact us at {{ config('billing.company_email', config('mail.from.address')) }}</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Siliconile') }}. All rights reserved.</p>
    </div>
</body>
</html>
