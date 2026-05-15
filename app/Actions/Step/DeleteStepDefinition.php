<?php

namespace App\Actions\Step;

use App\Models\StepDefinition;
use Illuminate\Support\Facades\DB;

class DeleteStepDefinition
{
    public function handle(StepDefinition $step): void
    {
        DB::transaction(function () use ($step) {
            $templateId = $step->template_id;
            $deletedOrder = $step->order;

            $step->update(['order' => -$step->id]);
            $step->delete();

            $remaining = DB::table('step_definitions')
                ->where('template_id', $templateId)
                ->whereNull('deleted_at')
                ->where('order', '>', $deletedOrder)
                ->orderBy('order')
                ->pluck('id', 'order');

            foreach ($remaining as $order => $id) {
                DB::table('step_definitions')
                    ->where('id', $id)
                    ->update(['order' => $order - 1, 'updated_at' => now()]);
            }
        });
    }
}
