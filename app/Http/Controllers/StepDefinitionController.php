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
        $action->handle($template, $request->validated());

        return redirect()
            ->route('process-templates.show', $template)
            ->with('success', 'Bước đã được thêm thành công.');
    }

    public function update(UpdateStepRequest $request, StepDefinition $stepDefinition, UpdateStepDefinition $action): RedirectResponse
    {
        $action->handle($stepDefinition, $request->validated());

        return redirect()
            ->route('process-templates.show', $stepDefinition->template_id)
            ->with('success', 'Bước đã được cập nhật.');
    }

    public function destroy(StepDefinition $stepDefinition, DeleteStepDefinition $action): RedirectResponse
    {
        $this->authorize('update', $stepDefinition->processTemplate);
        $templateId = $stepDefinition->template_id;
        $action->handle($stepDefinition);

        return redirect()
            ->route('process-templates.show', $templateId)
            ->with('success', 'Bước đã xóa.');
    }

    public function reorder(ReorderStepRequest $request, StepDefinition $stepDefinition, ReorderStepDefinition $action): JsonResponse
    {
        $steps = $action->handle($stepDefinition, $request->validated()['new_order']);

        return response()->json([
            'steps' => StepDefinitionResource::collection($steps)->resolve(),
        ]);
    }
}
