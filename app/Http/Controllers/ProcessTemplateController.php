<?php

namespace App\Http\Controllers;

use App\Actions\Template\PublishTemplate;
use App\Actions\Template\UnpublishTemplate;
use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Http\Resources\ProcessTemplateResource;
use App\Http\Resources\StepDefinitionResource;
use App\Models\ProcessTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProcessTemplateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ProcessTemplate::class);

        // Designers/Admins see all, Managers only see Published
        $query = ProcessTemplate::withCount('stepDefinitions')->latest();

        if (! auth()->user()->hasPermissionTo('manage_templates')) {
            $query->published();
        }

        $templates = $query->get();

        return Inertia::render('Templates/Index', [
            'templates' => ProcessTemplateResource::collection($templates)->resolve(),
            'can' => [
                'create' => auth()->user()->can('create', ProcessTemplate::class),
            ],
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $template = ProcessTemplate::create([
            ...$request->validated(),
            'created_by' => auth()->user()->id,
            'version' => 1,
        ]);

        return redirect()
            ->route('process-templates.show', $template)
            ->with('success', 'Template đã được tạo. Bắt đầu thêm các bước.');
    }

    public function show(ProcessTemplate $processTemplate): Response
    {
        $this->authorize('view', $processTemplate);

        $processTemplate->load('stepDefinitions');

        return Inertia::render('Templates/Show', [
            'template' => ProcessTemplateResource::make($processTemplate),
            'steps' => StepDefinitionResource::collection($processTemplate->stepDefinitions)->resolve(),
            'can' => [
                'update' => auth()->user()->can('update', $processTemplate) && ! $processTemplate->is_published,
                'delete' => auth()->user()->can('delete', $processTemplate),
                'publish' => auth()->user()->can('publish', $processTemplate),
            ],
        ]);
    }

    public function update(UpdateTemplateRequest $request, ProcessTemplate $processTemplate): RedirectResponse
    {
        if ($processTemplate->is_published) {
            return redirect()->back()->with('error', 'Không thể sửa template đã xuất bản.');
        }

        $processTemplate->update($request->validated());

        $message = $processTemplate->is_published
            ? 'Template đã được cập nhật. Các quy trình mới khởi động sẽ dùng phiên bản mới.'
            : 'Template đã được cập nhật.';

        return redirect()
            ->back()
            ->with('success', $message);
    }

    public function destroy(ProcessTemplate $processTemplate): RedirectResponse
    {
        $this->authorize('delete', $processTemplate);

        if ($processTemplate->instances()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Template đang có quy trình đang chạy, không thể xóa.');
        }

        $processTemplate->delete();

        return redirect()
            ->route('process-templates.index')
            ->with('success', 'Template đã được xóa.');
    }

    public function publish(ProcessTemplate $processTemplate, PublishTemplate $action): RedirectResponse
    {
        $this->authorize('publish', $processTemplate);

        try {
            $action->handle($processTemplate);
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->with('error', $e->errors()['error'][0] ?? $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', 'Template đã được xuất bản. Manager hiện đã có thể sử dụng.');
    }

    public function unpublish(ProcessTemplate $processTemplate, UnpublishTemplate $action): RedirectResponse
    {
        $this->authorize('publish', $processTemplate);

        $action->handle($processTemplate);

        return redirect()
            ->back()
            ->with('success', 'Template đã được chuyển về bản nháp.');
    }
}
