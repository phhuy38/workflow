<?php

namespace App\States\StepExecution;

class Escalated extends StepExecutionState
{
    public static $name = 'escalated';

    public function getValue(): string
    {
        return 'escalated';
    }
}
