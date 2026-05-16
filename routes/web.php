<?php

use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProcessInstanceController;
use App\Http\Controllers\ProcessTemplateController;
use App\Http\Controllers\StepDefinitionController;
use App\Http\Controllers\StepExecutionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/force-reset-password', function () {
        if (!auth()->user()->requires_password_reset) {
            return redirect()->route('dashboard');
        }
        return inertia('Auth/ForceResetPassword');
    })->name('password.force-reset');

    Route::post('/force-reset-password', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'requires_password_reset' => false,
        ]);

        \Illuminate\Support\Facades\Auth::login($request->user());

        return redirect()->route('dashboard')->with('success', 'Mật khẩu đã được cập nhật.');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');

    // Story 2.1: Template management (index, store, show) + Story 2.3 (update, destroy) + Story 2.4 (publish/unpublish)
    Route::resource('process-templates', ProcessTemplateController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('process-templates/{process_template}/publish', [ProcessTemplateController::class, 'publish'])
        ->name('process-templates.publish');
    Route::post('process-templates/{process_template}/unpublish', [ProcessTemplateController::class, 'unpublish'])
        ->name('process-templates.unpublish');

    // Story 2.2: Step definition management
    Route::resource('step-definitions', StepDefinitionController::class)
        ->only(['store', 'update', 'destroy']);
    Route::patch('step-definitions/{step_definition}/reorder', [StepDefinitionController::class, 'reorder'])
        ->name('step-definitions.reorder');

    // Story 3.1: Process instance execution
    Route::resource('process-instances', ProcessInstanceController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::post('process-instances/{process_instance}/cancel', [ProcessInstanceController::class, 'cancel'])
        ->name('process-instances.cancel');
    // Story 3.2 & 3.4: Step execution interactions
    Route::post('step-executions/{step_execution}/acknowledge', [StepExecutionController::class, 'acknowledge'])
        ->name('step-executions.acknowledge');
    Route::post('step-executions/{step_execution}/complete', [StepExecutionController::class, 'complete'])
        ->name('step-executions.complete');
    Route::post('step-executions/{step_execution}/override', [StepExecutionController::class, 'override'])
        ->name('step-executions.override');
    Route::post('step-executions/{step_execution}/messages', [\App\Http\Controllers\StepMessageController::class, 'store'])
        ->name('step-messages.store');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    Route::post('users/{user}/assign-designer', [UserController::class, 'assignDesigner'])->name('users.assign-designer');
    Route::post('users/{user}/revoke-designer', [UserController::class, 'revokeDesigner'])->name('users.revoke-designer');

    // System Settings
    Route::get('system', [SystemController::class, 'index'])->name('system.index');
    Route::put('system', [SystemController::class, 'update'])->name('system.update');
    // Rate limit test-email to prevent SMTP server abuse
    Route::post('system/test-email', [SystemController::class, 'testEmail'])
        ->middleware('throttle:5,1') // 5 requests per minute
        ->name('system.test-email');
});

require __DIR__.'/settings.php';
