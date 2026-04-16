<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    Route::post('users/{user}/assign-designer', [UserController::class, 'assignDesigner'])->name('users.assign-designer');
    Route::post('users/{user}/revoke-designer', [UserController::class, 'revokeDesigner'])->name('users.revoke-designer');
});

require __DIR__.'/settings.php';
