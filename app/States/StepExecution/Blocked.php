<?php

namespace App\States\StepExecution;

class Blocked extends StepExecutionState
{
    public static $name = 'blocked';

    public function getValue(): string
    {
        return 'blocked';
    }
}
