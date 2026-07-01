<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCleRecordCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $cleRecord,
        public $requestData = []
    ) {}
}
