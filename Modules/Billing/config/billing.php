<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code for invoices. Uses ISO 4217 currency codes.
    |
    */
    'default_currency' => env('BILLING_CURRENCY', 'EGP'),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | The default VAT/tax rate percentage for invoices.
    | Saudi Arabia standard VAT is 15%.
    |
    */
    'default_tax_rate' => env('BILLING_TAX_RATE', 15.00),

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used when generating sequential invoice numbers.
    | Format: {prefix}-{year}-{sequence}
    | Example: INV-2025-0001
    |
    */
    'invoice_number_prefix' => env('BILLING_INVOICE_PREFIX', 'INV'),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Terms (Days)
    |--------------------------------------------------------------------------
    |
    | The default number of days from issue date until the due date.
    |
    */
    'default_payment_terms_days' => env('BILLING_PAYMENT_TERMS_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Default Invoice Terms & Conditions
    |--------------------------------------------------------------------------
    |
    | Default terms and conditions text that appears on invoices.
    |
    */
    'default_invoice_terms' => env('BILLING_INVOICE_TERMS', 'Payment is due within the specified due date. Late payments may incur additional charges.'),

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Company details that appear on invoices.
    |
    */
    'company_name' => env('COMPANY_NAME', config('app.name')),
    'company_address' => env('COMPANY_ADDRESS', ''),
    'company_phone' => env('COMPANY_PHONE', ''),
    'company_email' => env('COMPANY_EMAIL', config('mail.from.address')),
    'company_vat_number' => env('COMPANY_VAT_NUMBER', ''),
    'company_logo' => env('COMPANY_LOGO', ''),

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for PDF generation.
    |
    */
    'pdf' => [
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'storage_disk' => 'local',
        'storage_path' => 'invoices',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Mark Overdue
    |--------------------------------------------------------------------------
    |
    | Whether to automatically mark invoices as overdue when past due date.
    | This is handled by the scheduled task.
    |
    */
    'auto_mark_overdue' => env('BILLING_AUTO_MARK_OVERDUE', true),

    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice-related email notifications.
    |
    */
    'notifications' => [
        'send_on_finalize' => env('BILLING_NOTIFY_ON_FINALIZE', true),
        'send_on_payment' => env('BILLING_NOTIFY_ON_PAYMENT', true),
        'send_overdue_reminders' => env('BILLING_SEND_OVERDUE_REMINDERS', true),
        'overdue_reminder_days' => [3, 7, 14], // Days after due date to send reminders
    ],
];
