<?php

namespace App\States\StepExecution;

class Skipped extends StepExecutionState
{
    public static $name = 'skipped';

    public function getValue(): string
    {
        return 'skipped';
    }
}
