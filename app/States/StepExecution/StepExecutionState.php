<?php

namespace App\States\StepExecution;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class StepExecutionState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, InProgress::class)
            ->allowTransition(InProgress::class, Completed::class)
            ->allowTransition(InProgress::class, Blocked::class)
            ->allowTransition(Blocked::class, InProgress::class)
            ->allowTransition(InProgress::class, Escalated::class)
            ->allowTransition(Pending::class, Skipped::class) // For manager override
            ->allowTransition(InProgress::class, Skipped::class)
            ->allowTransition(Blocked::class, Skipped::class)
            ->allowTransition(Escalated::class, Skipped::class);
    }

    // abstract public function getValue(): string;
}
