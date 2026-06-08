<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewHearingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $hearing,
        public $requestData = []
    ) {}
}
