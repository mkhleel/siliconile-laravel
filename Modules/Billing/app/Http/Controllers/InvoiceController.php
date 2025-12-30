<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Billing\Mail\InvoiceMail;
use Modules\Billing\Models\Invoice;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the user's invoices.
     */
    public function index(Request $request): View
    {
        return view('billing::invoices.index');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);

        return view('billing::invoices.show', [
            'invoice' => $invoice->load('items', 'billable'),
        ]);
    }

    /**
     * Generate and download invoice PDF.
     */
    public function downloadPdf(Invoice $invoice): StreamedResponse
    {
        $this->authorizeInvoice($invoice);

        $pdf = Pdf::loadView('billing::pdf.invoice', [
            'invoice' => $invoice->load('items', 'billable'),
        ]);

        $filename = $invoice->number
            ? "invoice-{$invoice->number}.pdf"
            : "invoice-draft-{$invoice->id}.pdf";

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Send invoice to customer via email.
     */
    public function sendToCustomer(Invoice $invoice): RedirectResponse
    {
        // Only admins can send invoices
        if (!auth()->user()?->can('manage', $invoice)) {
            abort(403, 'Unauthorized access');
        }

        $email = $invoice->billable_email;

        if (!$email) {
            return redirect()->back()->with('error', 'Customer email not found.');
        }

        \Illuminate\Support\Facades\Mail::to($email)->send(new InvoiceMail($invoice));

        return redirect()->back()->with('success', 'Invoice has been sent to the customer.');
    }

    /**
     * Show payment page for the invoice.
     */
    public function pay(Invoice $invoice): RedirectResponse|View
    {
        $this->authorizeInvoice($invoice);

        if (!$invoice->canBePaid()) {
            return redirect()
                ->route('billing.invoice.show', $invoice)
                ->with('error', 'This invoice cannot be paid at this time.');
        }

        return view('billing::invoices.pay', [
            'invoice' => $invoice->load('items', 'billable'),
        ]);
    }

    /**
     * Handle payment success callback.
     */
    public function paymentSuccess(Request $request): View
    {
        $orderId = $request->get('order_id');
        $paymentId = $request->get('payment_id');

        return view('billing::payment.success', compact('orderId', 'paymentId'));
    }

    /**
     * Handle payment cancellation callback.
     */
    public function paymentCancel(Request $request): View
    {
        $orderId = $request->get('order_id');

        return view('billing::payment.cancel', compact('orderId'));
    }

    /**
     * Authorize that the current user can view/interact with the invoice.
     */
    protected function authorizeInvoice(Invoice $invoice): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        // Admins can view all invoices
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        $billable = $invoice->billable;
        $authorized = false;

        // Direct user match
        if ($billable instanceof \App\Models\User && $billable->id === $user->id) {
            $authorized = true;
        }

        // Member match
        if ($billable instanceof \Modules\Membership\Models\Member) {
            if ($billable->user_id === $user->id) {
                $authorized = true;
            }
        }

        // User's member match
        if (!$authorized && method_exists($user, 'member') && $user->member) {
            if ($invoice->billable_type === get_class($user->member)
                && $invoice->billable_id === $user->member->id) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            abort(403, 'You are not authorized to view this invoice.');
        }
    }
}
