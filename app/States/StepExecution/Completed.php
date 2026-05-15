<?php

namespace App\States\StepExecution;

class Completed extends StepExecutionState
{
    public static $name = 'completed';

    public function getValue(): string
    {
        return 'completed';
    }
}
