<?php

namespace App\Actions\Step;

use App\Models\StepDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderStepDefinition
{
    /**
     * Move a step to a new order position within its template.
     * Uses temporary order to avoid unique constraint violations during shift.
     *
     * @return Collection<int, StepDefinition>
     */
    public function handle(StepDefinition $step, int $newOrder): Collection
    {
        $templateId = $step->template_id;
        $currentOrder = $step->order;

        $maxOrder = StepDefinition::where('template_id', $templateId)->whereNull('deleted_at')->max('order');
        $newOrder = max(1, min($newOrder, $maxOrder ?? 1));

        if ($newOrder === $currentOrder) {
            return StepDefinition::where('template_id', $templateId)->orderBy('order')->get();
        }

        DB::transaction(function () use ($step, $templateId, $currentOrder, $newOrder, $maxOrder) {
            $tempOrder = $maxOrder + 100;

            // Move the target step to a safe temp position
            DB::table('step_definitions')
                ->where('id', $step->id)
                ->update(['order' => $tempOrder]);

            if ($newOrder < $currentOrder) {
                // Moving up: shift steps in range [newOrder, currentOrder-1] down by 1
                DB::table('step_definitions')
                    ->where('template_id', $templateId)
                    ->whereNull('deleted_at')
                    ->whereBetween('order', [$newOrder, $currentOrder - 1])
                    ->increment('order');
            } else {
                // Moving down: shift steps in range [currentOrder+1, newOrder] up by 1
                DB::table('step_definitions')
                    ->where('template_id', $templateId)
                    ->whereNull('deleted_at')
                    ->whereBetween('order', [$currentOrder + 1, $newOrder])
                    ->decrement('order');
            }

            // Place the step at its final position
            DB::table('step_definitions')
                ->where('id', $step->id)
                ->update(['order' => $newOrder]);
        });

        return StepDefinition::where('template_id', $templateId)->orderBy('order')->get();
    }
}
