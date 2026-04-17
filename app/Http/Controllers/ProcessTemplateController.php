<?php

namespace App\Http\Controllers;

use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Resources\ProcessTemplateResource;
use App\Models\ProcessTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProcessTemplateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', ProcessTemplate::class); // DÒNG ĐẦU TIÊN — manage_templates only

        $templates = ProcessTemplate::withCount('stepDefinitions')
            ->latest()
            ->get();

        return Inertia::render('Templates/Index', [
            'templates' => ProcessTemplateResource::collection($templates)->resolve(),
            'can' => [
                'create' => auth()->user()->can('create', ProcessTemplate::class),
            ],
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', ProcessTemplate::class); // DÒNG ĐẦU TIÊN

        $template = ProcessTemplate::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('process-templates.show', $template)
            ->with('success', 'Template đã được tạo. Bắt đầu thêm các bước.');
    }

    public function show(ProcessTemplate $processTemplate): Response
    {
        $this->authorize('update', $processTemplate); // DÒNG ĐẦU TIÊN

        $processTemplate->loadCount('stepDefinitions');

        return Inertia::render('Templates/Show', [
            'template' => ProcessTemplateResource::make($processTemplate),
            'can' => [
                'update' => auth()->user()->can('update', $processTemplate),
                'delete' => auth()->user()->can('delete', $processTemplate),
                'publish' => auth()->user()->can('publish', $processTemplate),
            ],
        ]);
    }
}
