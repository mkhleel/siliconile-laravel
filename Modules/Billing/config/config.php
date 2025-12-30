<?php

return [
    'name' => 'Billing',

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency used for orders and invoices.
    |
    */
    'default_currency' => env('BILLING_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    |
    | Configure default tax rates and settings.
    |
    */
    'default_tax_rate' => env('BILLING_DEFAULT_TAX_RATE', 0.00),
    'tax_enabled' => env('BILLING_TAX_ENABLED', true),
    'tax_included' => env('BILLING_TAX_INCLUDED', false),

    /*
    |--------------------------------------------------------------------------
    | Order Settings
    |--------------------------------------------------------------------------
    |
    | Configure order generation settings.
    |
    */
    'order_prefix' => env('BILLING_ORDER_PREFIX', 'ORD-'),

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Your company information for invoices and receipts.
    |
    */
    'company_name' => env('BILLING_COMPANY_NAME', config('app.name')),
    'company_address' => env('BILLING_COMPANY_ADDRESS', '123 Business St, City, Country'),
    'company_phone' => env('BILLING_COMPANY_PHONE', '+1234567890'),
    'company_email' => env('BILLING_COMPANY_EMAIL', 'billing@example.com'),
    'company_logo' => env('BILLING_COMPANY_LOGO', '/logo.png'),
    'company_vat' => env('BILLING_COMPANY_VAT', null),
    'company_vat_number' => env('BILLING_COMPANY_VAT_NUMBER', ''),

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Configure invoice generation and numbering.
    |
    */
    'invoice_number_prefix' => env('BILLING_INVOICE_PREFIX', 'INV'),
    'default_payment_terms_days' => env('BILLING_PAYMENT_TERMS_DAYS', 14),
    'default_invoice_terms' => env('BILLING_INVOICE_TERMS', 'Payment is due within the specified due date. Late payments may incur additional charges.'),

    /*
    |--------------------------------------------------------------------------
    | PDF Generation
    |--------------------------------------------------------------------------
    |
    | Configure PDF generation for invoices.
    |
    */
    'pdf_paper_size' => env('BILLING_PDF_PAPER_SIZE', 'a4'),
    'pdf_orientation' => env('BILLING_PDF_ORIENTATION', 'portrait'),
    'pdf_storage_disk' => env('BILLING_PDF_STORAGE_DISK', 'local'),
    'pdf_storage_path' => env('BILLING_PDF_STORAGE_PATH', 'invoices'),

    /*
    |--------------------------------------------------------------------------
    | Payment Integration Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for payment integrations.
    |
    */
    'default_payment_gateway' => env('BILLING_DEFAULT_PAYMENT_GATEWAY', 'stripe'),
    'payment_success_redirect' => env('BILLING_PAYMENT_SUCCESS_REDIRECT', '/billing/payment/success'),
    'payment_cancel_redirect' => env('BILLING_PAYMENT_CANCEL_REDIRECT', '/billing/payment/cancel'),

    /*
    |--------------------------------------------------------------------------
    | Receipt Template
    |--------------------------------------------------------------------------
    |
    | The template to use for receipts.
    |
    */
    'receipt_template' => env('BILLING_RECEIPT_TEMPLATE', 'billing::receipts.default'),

    /*
    |--------------------------------------------------------------------------
    | Invoice Lifecycle Automation
    |--------------------------------------------------------------------------
    |
    | Auto-mark overdue invoices after due date has passed.
    |
    */
    'auto_mark_overdue' => env('BILLING_AUTO_MARK_OVERDUE', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure when to send invoice notifications.
    |
    */
    'notifications' => [
        'send_on_finalize' => env('BILLING_NOTIFY_ON_FINALIZE', true),
        'send_on_payment' => env('BILLING_NOTIFY_ON_PAYMENT', true),
        'send_overdue_reminders' => env('BILLING_SEND_OVERDUE_REMINDERS', true),
        'overdue_reminder_days' => [3, 7, 14],
    ],

];
