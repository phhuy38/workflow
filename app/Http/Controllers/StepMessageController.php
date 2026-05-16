<?php

namespace App\Http\Controllers;

use App\Actions\Process\SendMessageToStep;
use App\Models\StepExecution;
use Illuminate\Http\Request;

class StepMessageController extends Controller
{
    public function store(Request $request, StepExecution $stepExecution, SendMessageToStep $action)
    {
        $this->authorize('addMessage', $stepExecution);

        if (is_null($stepExecution->assigned_to)) {
            abort(400, 'Bước này chưa được phân công cho ai.');
        }

        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $action->handle($stepExecution, auth()->user(), $validated['body']);

        return redirect()->back()->with('success', 'Đã gửi tin nhắn.');
    }
}
