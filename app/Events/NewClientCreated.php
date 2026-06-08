<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewClientCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $client,
        public $requestData = []
    ) {}
}
