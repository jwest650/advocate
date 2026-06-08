<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTaskCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $task,
        public $requestData = []
    ) {}
}
