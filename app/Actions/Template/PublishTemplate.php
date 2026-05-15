<?php

namespace App\Actions\Template;

use App\Models\ProcessTemplate;
use Illuminate\Validation\ValidationException;

class PublishTemplate
{
    public function handle(ProcessTemplate $template): void
    {
        $template->load('stepDefinitions');
        $steps = $template->stepDefinitions;
        $errors = [];

        if ($steps->isEmpty()) {
            throw ValidationException::withMessages([
                'error' => 'Template phải có ít nhất 1 bước để publish.',
            ]);
        }

        foreach ($steps as $index => $step) {
            $stepNum = $index + 1;
            if (empty($step->assignee_type)) {
                $errors[] = "Bước {$stepNum}: chưa có loại người phụ trách.";
            }
            if ($step->assignee_type === 'user' && ! $step->assignee_id) {
                $errors[] = "Bước {$stepNum}: chưa chọn người phụ trách cụ thể.";
            }
            if ($step->duration_hours <= 0) {
                $errors[] = "Bước {$stepNum}: thời hạn phải lớn hơn 0.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'error' => implode(' ', $errors),
            ]);
        }

        $template->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
