<?php

namespace App\Http\Controllers\Admin;

use App\Actions\User\AssignDesignerRole;
use App\Actions\User\DeactivateUser;
use App\Actions\User\RevokeDesignerRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->orderBy('full_name')
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => new UserResource($user),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update([
            'full_name' => $request->full_name,
            'email'     => $request->email,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        app(DeactivateUser::class)->handle(auth()->user(), $user);

        return back()->with('success', "User {$user->full_name} has been deactivated.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => true]);

        activity()->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['action' => 'reactivate'])
            ->log('user_reactivated');

        return back()->with('success', "User {$user->full_name} has been reactivated.");
    }

    public function assignDesigner(User $user): RedirectResponse
    {
        $this->authorize('viewAny', User::class); // admin-only: manage_users permission, prevents self-escalation

        app(AssignDesignerRole::class)->handle(auth()->user(), $user);

        return back()->with('success', "Process Designer role assigned to {$user->full_name}.");
    }

    public function revokeDesigner(User $user): RedirectResponse
    {
        $this->authorize('viewAny', User::class); // admin-only: manage_users permission, prevents self-escalation

        app(RevokeDesignerRole::class)->handle(auth()->user(), $user);

        return back()->with('success', "Process Designer role revoked from {$user->full_name}.");
    }
}
