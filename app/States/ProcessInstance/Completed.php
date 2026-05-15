<?php

namespace App\States\ProcessInstance;

class Completed extends ProcessInstanceState
{
    public static $name = 'completed';

    public function getValue(): string
    {
        return 'completed';
    }
}
