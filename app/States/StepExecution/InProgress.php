<?php

namespace App\States\StepExecution;

class InProgress extends StepExecutionState
{
    public static $name = 'in_progress';

    public function getValue(): string
    {
        return 'in_progress';
    }
}
