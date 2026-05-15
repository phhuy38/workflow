<?php

namespace App\States\StepExecution;

class Pending extends StepExecutionState
{
    public static $name = 'pending';

    public function getValue(): string
    {
        return 'pending';
    }
}
