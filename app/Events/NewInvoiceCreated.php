<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewInvoiceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $invoice,
        public $requestData = []
    ) {}
}
