<?php

namespace App\Http\Controllers;

use App\Actions\Process\LaunchProcessInstance;
use App\Http\Requests\Process\LaunchProcessRequest;
use App\Http\Resources\ProcessInstanceResource;
use App\Http\Resources\ProcessTemplateResource;
use App\Http\Resources\StepExecutionResource;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProcessInstanceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ProcessInstance::class);

        $query = ProcessInstance::with(['template', 'creator'])->latest();

        // RBAC filtering (ADR-004)
        if (! auth()->user()->hasRole(['admin', 'manager', 'process_designer'])) {
            // Logic for executor/beneficiary filtering would go here
            // For now, only privileged roles see index
            $query->where('launched_by', auth()->id());
        }

        $instances = $query->get();

        return Inertia::render('Instances/Index', [
            'instances' => ProcessInstanceResource::collection($instances)->resolve(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ProcessInstance::class);

        $templates = ProcessTemplate::published()->withCount('stepDefinitions')->get();

        return Inertia::render('Instances/Create', [
            'templates' => ProcessTemplateResource::collection($templates)->resolve(),
        ]);
    }

    public function store(LaunchProcessRequest $request, LaunchProcessInstance $action): RedirectResponse
    {
        $template = ProcessTemplate::findOrFail($request->template_id);

        $instance = $action->handle($template, $request->validated(), auth()->user());

        return redirect()
            ->route('process-instances.show', $instance)
            ->with('success', 'Quy trình đã được khởi động.');
    }

    public function show(ProcessInstance $processInstance): Response
    {
        $this->authorize('view', $processInstance);

        $processInstance->load(['template', 'creator', 'stepExecutions.assignee']);

        return Inertia::render('Instances/Show', [
            'instance' => ProcessInstanceResource::make($processInstance),
            'steps' => StepExecutionResource::collection($processInstance->stepExecutions)->resolve(),
        ]);
    }
}
