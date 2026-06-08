<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewJudgeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $judge,
        public $requestData = []
    ) {}
}
