<?php

namespace App\States\ProcessInstance;

class Pending extends ProcessInstanceState
{
    public static $name = 'pending';

    public function getValue(): string
    {
        return 'pending';
    }
}
