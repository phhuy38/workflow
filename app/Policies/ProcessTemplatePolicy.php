<?php

namespace App\Policies;

use App\Models\User;

class ProcessTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['manage_templates', 'launch_instances', 'view_all_instances']);
    }

    // P3: Added $template parameter for object-level authorization (ProcessTemplate model created in Story 2.1)
    public function view(User $user, mixed $template = null): bool
    {
        return $user->hasAnyPermission(['manage_templates', 'launch_instances', 'view_all_instances']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_templates');
    }

    // P3: Added $template parameter for object-level authorization
    public function update(User $user, mixed $template = null): bool
    {
        return $user->hasPermissionTo('manage_templates');
    }

    // P3: Added $template parameter for object-level authorization
    public function delete(User $user, mixed $template = null): bool
    {
        return $user->hasPermissionTo('manage_templates');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermissionTo('publish_templates');
    }
}
