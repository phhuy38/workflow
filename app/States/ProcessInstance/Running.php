<?php

namespace App\States\ProcessInstance;

class Running extends ProcessInstanceState
{
    public static $name = 'running';

    public function getValue(): string
    {
        return 'running';
    }
}
