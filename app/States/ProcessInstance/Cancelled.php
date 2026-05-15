<?php

namespace App\States\ProcessInstance;

class Cancelled extends ProcessInstanceState
{
    public static $name = 'cancelled';

    public function getValue(): string
    {
        return 'cancelled';
    }
}
