<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCaseCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $case,
        public $requestData = []
    ) {}
}
