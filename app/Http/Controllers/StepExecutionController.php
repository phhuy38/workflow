<?php

namespace App\Http\Controllers;

use App\Actions\Process\AcknowledgeStep;
use App\Actions\Process\CompleteStep;
use App\Models\StepExecution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StepExecutionController extends Controller
{
    public function acknowledge(StepExecution $stepExecution, AcknowledgeStep $action): RedirectResponse
    {
        $this->authorize('acknowledge', $stepExecution);

        $action->handle($stepExecution, auth()->user());

        return redirect()->back()->with('success', 'Đã xác nhận nhận việc.');
    }

    public function complete(Request $request, StepExecution $stepExecution, CompleteStep $action): RedirectResponse
    {
        $this->authorize('complete', $stepExecution);

        $validated = $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $action->handle($stepExecution, auth()->user(), $validated);

        return redirect()->back()->with('success', 'Đã hoàn thành bước.');
    }
}
