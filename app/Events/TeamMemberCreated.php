<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $teamMember,
        public $requestData
    ) {}
}