<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'can' => $user ? [
                    'manage_templates' => $user->can('manage_templates'),
                    'publish_templates' => $user->can('publish_templates'),
                    'launch_instances' => $user->can('launch_instances'),
                    'view_all_instances' => $user->can('view_all_instances'),
                    'manage_instances' => $user->can('manage_instances'),
                    'complete_assigned_steps' => $user->can('complete_assigned_steps'),
                    'view_own_instances' => $user->can('view_own_instances'),
                    'manage_users' => $user->can('manage_users'),
                    'manage_system' => $user->can('manage_system'),
                ] : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
