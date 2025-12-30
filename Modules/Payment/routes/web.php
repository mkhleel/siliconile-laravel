<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Models\Order;
use Modules\Payment\Http\Controllers\PaymentVerificationController;
use Modules\Payment\Http\Controllers\WebhookController;
use Modules\Shop\Facades\Cart;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('payment')->name('payment.')->group(function () {
    // Payment verification route - redirect from payment gateway
    Route::match(['GET', 'POST'], 'verify/{gateway}', [PaymentVerificationController::class, 'verify'])
        ->name('verify');

    // Payment success/failure/cancelled pages
    Route::get('success/{order}', function ($order) {
        abort_if(!Order::find($order), 404);
        // empty cart

        Cart::clear();
        session()->forget(['checkout_coupon', 'checkout_discount', 'checkout_form_data']);

        Order::find($order)->setStatus('completed'); // Update order status to completed
        return view('payment::success', ['order' => $order]);
    })->name('success');

    Route::get('failed', function () {
        return view('payment::failed');
    })->name('failed');

    Route::get('cancelled', function () {
        return view('payment::cancelled');
    })->name('cancelled');

    Route::get('error', function () {
        return view('payment::error');
    })->name('error');

    // Webhook routes - these should be public and not have CSRF protection
    Route::prefix('webhook')->name('webhook.')->group(function () {
        Route::post('{gateway}', [WebhookController::class, 'handleWebhook'])
            ->withoutMiddleware(['web'])
            ->middleware(['api'])
            ->name('handler');
    });

    // Example integration page
    Route::get('example', function () {
        return view('payment::integration-example');
    })->name('example');
});
