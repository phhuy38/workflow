<?php

namespace App\Http\Controllers;

use App\Actions\Process\CancelInstance;
use App\Actions\Process\LaunchProcessInstance;
use App\Http\Requests\Process\LaunchProcessRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ProcessInstanceResource;
use App\Http\Resources\ProcessTemplateResource;
use App\Http\Resources\StepExecutionResource;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

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

        $activities = [];
        $canViewFullLog = auth()->user()->can('viewFullLog', $processInstance);

        if ($canViewFullLog) {
            $stepIds = $processInstance->stepExecutions->pluck('id');
            $rawActivities = Activity::where(function ($q) use ($processInstance) {
                $q->where('subject_type', ProcessInstance::class)
                    ->where('subject_id', $processInstance->id);
            })->orWhere(function ($q) use ($stepIds) {
                $q->where('subject_type', StepExecution::class)
                    ->whereIn('subject_id', $stepIds);
            })->with(['causer', 'subject'])->latest()->orderByDesc('id')->get();
            $activities = ActivityResource::collection($rawActivities)->resolve();
        }

        return Inertia::render('Instances/Show', [
            'instance' => ProcessInstanceResource::make($processInstance),
            'steps' => StepExecutionResource::collection($processInstance->stepExecutions)->resolve(),
            'activities' => $activities,
            'can' => [
                'cancel' => auth()->user()->can('cancel', $processInstance),
                'override' => auth()->user()->can('override', $processInstance),
                'view_full_log' => $canViewFullLog,
            ],
        ]);
    }

    public function cancel(Request $request, ProcessInstance $processInstance, CancelInstance $action): RedirectResponse
    {
        $this->authorize('cancel', $processInstance);

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:1000',
        ]);

        $action->handle($processInstance, auth()->user(), $validated['reason']);

        return redirect()->back()->with('success', 'Quy trình đã được hủy.');
    }
}
