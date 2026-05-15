<?php

namespace App\States\ProcessInstance;

class Paused extends ProcessInstanceState
{
    public static $name = 'paused';

    public function getValue(): string
    {
        return 'paused';
    }
}
