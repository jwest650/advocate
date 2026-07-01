<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCourtCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $court,
        public $requestData = []
    ) {}
}
