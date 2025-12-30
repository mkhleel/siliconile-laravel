<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\InvoiceController;

/*
|--------------------------------------------------------------------------
| Billing Web Routes
|--------------------------------------------------------------------------
|
| Here are the web routes for the Billing module.
|
*/

Route::middleware(['web', 'auth'])->prefix('billing')->name('billing.')->group(function () {
    // Invoice routes for customers
    Route::prefix('invoices')->name('invoice.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::get('/{invoice}/pay', [InvoiceController::class, 'pay'])->name('pay');
        Route::post('/{invoice}/send', [InvoiceController::class, 'sendToCustomer'])->name('send');
    });

    // Payment callback routes
    Route::get('payment/success', [InvoiceController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('payment/cancel', [InvoiceController::class, 'paymentCancel'])->name('payment.cancel');
});
