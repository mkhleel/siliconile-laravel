<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-header td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-header .title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .invoice-header .logo {
            width: 150px;
        }
        .customer-details {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .invoice-items {
            margin-top: 40px;
            border-collapse: collapse;
        }
        .invoice-items th {
            background: #eee;
            border-bottom: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .invoice-items td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .invoice-items .item-row:last-child td {
            border-bottom: none;
        }
        .totals-table {
            margin-top: 30px;
            width: 300px;
            float: right;
        }
        .totals-table td {
            padding: 5px;
        }
        .totals-table .total-row td {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .footer {
            margin-top: 80px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .payment-info {
            margin-top: 40px;
        }
        .payment-status {
            margin-top: 20px;
            padding: 10px;
            font-weight: bold;
            text-align: center;
            border-radius: 5px;
        }
        .payment-status.paid {
            background: #e6f9e6;
            color: #2ca02c;
        }
        .payment-status.pending {
            background: #fff9e6;
            color: #e6a23c;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="invoice-header">
            <tr>
                <td class="title">
                    INVOICE
                    <br>
                    <span style="font-size: 16px;">#{{ $invoice->invoice_number }}</span>
                </td>
                <td class="right">
                    @if($company['logo'])
                    <img src="{{ $company['logo'] }}" alt="Company Logo" class="logo">
                    @else
                    <h2>{{ $company['name'] }}</h2>
                    @endif
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="customer-details">
                        <strong>Billed To:</strong><br>
                        {{ $user->name }}<br>
                        {{ $user->email }}<br>
                        @if($invoice->billing_address)
                            {{ $invoice->billing_address['address'] ?? '' }}<br>
                            {{ $invoice->billing_address['city'] ?? '' }}, {{ $invoice->billing_address['state'] ?? '' }} {{ $invoice->billing_address['zip'] ?? '' }}<br>
                            {{ $invoice->billing_address['country'] ?? '' }}
                        @endif
                    </div>
                </td>
                <td style="width: 50%;" class="right">
                    <div>
                        <strong>Invoice Date:</strong> {{ $invoice->created_at->format('F j, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('F j, Y') }}<br>
                        <strong>Status:</strong> {{ ucfirst($invoice->status) }}<br>
                    </div>
                </td>
            </tr>
        </table>

        <table class="invoice-items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr class="item-row">
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{!! formatCurrency($item->price, 2) !!}</td>
                    <td class="right">{!! formatCurrency($item->price * $item->quantity, 2) !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="right">{!! formatCurrency($invoice->amount, 2) !!}</td>
            </tr>
            @if($invoice->tax_amount > 0)
            <tr>
                <td>Tax:</td>
                <td class="right">{!! formatCurrency($invoice->tax_amount, 2) !!}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="right">{!! formatCurrency($invoice->total_amount, 2) !!}</td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <div class="payment-info">
            <h3>Payment Information</h3>
            <p>Please include the invoice number with your payment.</p>
            
            <div class="payment-details">
                <p>
                    <strong>Bank Name:</strong> {{ config('billing.bank_name', 'Bank of Example') }}<br>
                    <strong>Account Name:</strong> {{ config('billing.account_name', $company['name']) }}<br>
                    <strong>Account Number:</strong> {{ config('billing.account_number', 'XXXX-XXXX-XXXX-XXXX') }}<br>
                    <strong>Routing Number:</strong> {{ config('billing.routing_number', 'XXXXXXXXXXXXX') }}<br>
                </p>
            </div>
        </div>

        <div class="payment-status {{ $invoice->status === 'paid' ? 'paid' : 'pending' }}">
            {{ $invoice->status === 'paid' ? 'PAID' : 'PAYMENT PENDING' }}
        </div>

        <div class="footer">
            <p>
                {{ $company['name'] }} | {{ $company['address'] }}<br>
                {{ $company['phone'] }} | {{ $company['email'] }}<br>
                @if(isset($company['vat']))
                VAT: {{ $company['vat'] }}
                @endif
            </p>
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>