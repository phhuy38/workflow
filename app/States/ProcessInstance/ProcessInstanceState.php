<?php

namespace App\States\ProcessInstance;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ProcessInstanceState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Running::class)
            ->allowTransition(Running::class, Completed::class)
            ->allowTransition(Running::class, Cancelled::class)
            ->allowTransition(Running::class, Paused::class)
            ->allowTransition(Paused::class, Running::class)
            ->allowTransition(Pending::class, Cancelled::class);
    }

    // abstract public function getValue(): string;
}
