<?php

declare(strict_types=1);

namespace Modules\Billing\Console;

use Illuminate\Console\Command;
use Modules\Billing\Services\InvoiceService;

class MarkOverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:mark-overdue';

    /**
     * The console command description.
     */
    protected $description = 'Mark invoices as overdue when past their due date';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $invoiceService): int
    {
        $this->info('Checking for overdue invoices...');

        $count = $invoiceService->markOverdueInvoices();

        if ($count > 0) {
            $this->info("Marked {$count} invoice(s) as overdue.");
        } else {
            $this->info('No invoices to mark as overdue.');
        }

        return self::SUCCESS;
    }
}
