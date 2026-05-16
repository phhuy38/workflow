<?php

namespace App\Http\Controllers;

use App\Http\Resources\InboxTaskResource;
use App\Models\StepExecution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        // Ensure only executor/admin/manager can access?
        // Wait, any user with 'executor' role should access. Manager/Admin can also be executors.
        // Beneficiary normally doesn't have pending tasks assigned to them directly in StepExecution (unless we allow it).
        // Let's just authorize based on having any role. The query itself scopes to user's assigned tasks.

        $tasks = StepExecution::with(['instance.template', 'instance.creator'])
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        $resourceCollection = InboxTaskResource::collection($tasks)->resolve();

        $order = [
            'overdue' => 1,
            'due_soon' => 2,
            'in_progress' => 3,
            'pending' => 4,
        ];

        usort($resourceCollection, function ($a, $b) use ($order) {
            $orderA = $order[$a['urgency_status']] ?? 99;
            $orderB = $order[$b['urgency_status']] ?? 99;

            if ($orderA === $orderB) {
                // If same urgency, sort by deadline (closest first)
                return $a['deadline_at'] <=> $b['deadline_at'];
            }

            return $orderA <=> $orderB;
        });

        return Inertia::render('Inbox/Index', [
            'tasks' => $resourceCollection,
        ]);
    }
}
