<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProcessInstanceResource;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\User;
use App\States\ProcessInstance\Cancelled;
use App\States\ProcessInstance\Completed;
use App\States\ProcessInstance\Running;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // For Manager/Admin/Designer
        if (auth()->user()->hasRole(['admin', 'manager', 'process_designer'])) {
            $validated = $request->validate([
                'search' => 'nullable|string',
                'template_id' => 'nullable|integer',
                'status' => 'nullable|string',
                'executor_id' => 'nullable|integer',
            ]);

            $query = ProcessInstance::with(['template', 'creator', 'stepExecutions']);

            if (isset($validated['search']) && $validated['search'] !== '') {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($validated['search']).'%']);
            }

            if (isset($validated['template_id']) && $validated['template_id'] !== '') {
                $query->where('template_id', $validated['template_id']);
            }

            if (isset($validated['status']) && $validated['status'] !== '') {
                // Ensure status class is allowed before querying
                $allowedStatuses = [
                    Running::class,
                    Completed::class,
                    Cancelled::class,
                ];
                if (in_array($validated['status'], $allowedStatuses)) {
                    $query->whereState('status', $validated['status']);
                }
            } else {
                // Default to Running instances
                $query->whereState('status', Running::class);
            }

            if (isset($validated['executor_id']) && $validated['executor_id'] !== '') {
                $query->whereHas('stepExecutions', function ($q) use ($validated) {
                    $q->whereIn('status', ['pending', 'in_progress'])
                        ->where('assigned_to', $validated['executor_id']);
                });
            }

            $instances = $query->get();

            $resourceCollection = ProcessInstanceResource::collection($instances)->resolve();

            // Sắp xếp: critical (đỏ) -> warning (vàng) -> normal (xanh)
            $order = ['critical' => 1, 'warning' => 2, 'normal' => 3];
            usort($resourceCollection, fn ($a, $b) => ($order[$a['traffic_light_status']] ?? 4) <=> ($order[$b['traffic_light_status']] ?? 4));

            // Load options for filter dropdowns
            $templates = ProcessTemplate::select('id', 'name')->orderBy('name')->get();
            $executors = User::role('executor')->select('id', 'full_name as name')->orderBy('full_name')->get();

            return Inertia::render('Dashboard', [
                'instances' => $resourceCollection,
                'can_view_manager_dashboard' => true,
                'filters' => $validated,
                'filterOptions' => [
                    'templates' => $templates,
                    'executors' => $executors,
                ],
            ]);
        }

        return Inertia::render('Dashboard', [
            'can_view_manager_dashboard' => false,
        ]);
    }
}
