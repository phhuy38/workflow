<?php

namespace App\Http\Controllers;

use App\Actions\Step\CreateStepDefinition;
use App\Actions\Step\DeleteStepDefinition;
use App\Actions\Step\ReorderStepDefinition;
use App\Actions\Step\UpdateStepDefinition;
use App\Http\Requests\Step\ReorderStepRequest;
use App\Http\Requests\Step\StoreStepRequest;
use App\Http\Requests\Step\UpdateStepRequest;
use App\Http\Resources\StepDefinitionResource;
use App\Models\ProcessTemplate;
use App\Models\StepDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class StepDefinitionController extends Controller
{
    public function store(StoreStepRequest $request, CreateStepDefinition $action): RedirectResponse
    {
        $template = ProcessTemplate::findOrFail($request->validated()['template_id']);

        if ($template->is_published) {
            return redirect()->back()->with('error', 'Không thể thêm bước vào template đã xuất bản.');
        }

        $action->handle($template, $request->validated());

        return redirect()
            ->route('process-templates.show', $template)
            ->with('success', 'Bước đã được thêm thành công.');
    }

    public function update(UpdateStepRequest $request, StepDefinition $stepDefinition, UpdateStepDefinition $action): RedirectResponse
    {
        if ($stepDefinition->processTemplate->is_published) {
            return redirect()->back()->with('error', 'Không thể sửa bước của template đã xuất bản.');
        }

        $action->handle($stepDefinition, $request->validated());

        return redirect()
            ->route('process-templates.show', $stepDefinition->template_id)
            ->with('success', 'Bước đã được cập nhật.');
    }

    public function destroy(StepDefinition $stepDefinition, DeleteStepDefinition $action): RedirectResponse
    {
        $this->authorize('update', $stepDefinition->processTemplate);

        if ($stepDefinition->processTemplate->is_published) {
            return redirect()->back()->with('error', 'Không thể xóa bước của template đã xuất bản.');
        }

        $templateId = $stepDefinition->template_id;
        $action->handle($stepDefinition);

        return redirect()
            ->route('process-templates.show', $templateId)
            ->with('success', 'Bước đã xóa.');
    }

    public function reorder(ReorderStepRequest $request, StepDefinition $stepDefinition, ReorderStepDefinition $action): JsonResponse|RedirectResponse
    {
        if ($stepDefinition->processTemplate->is_published) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Không thể đổi thứ tự bước của template đã xuất bản.'], 422)
                : redirect()->back()->with('error', 'Không thể đổi thứ tự bước của template đã xuất bản.');
        }

        $steps = $action->handle($stepDefinition, $request->validated()['new_order']);

        if ($request->wantsJson()) {
            return response()->json([
                'steps' => StepDefinitionResource::collection($steps)->resolve(),
            ]);
        }

        return redirect()
            ->route('process-templates.show', $stepDefinition->template_id)
            ->with('success', 'Thứ tự bước đã được cập nhật.');
    }
}
