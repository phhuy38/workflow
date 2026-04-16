<?php

namespace App\Providers;

use App\Events\UserDeactivated;
use App\Listeners\ReassignOpenStepsOnUserDeactivated;
use App\Listeners\UpdateLastLoginAt;
use App\Models\User;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->configurePolicies();
        Event::listen(Login::class, UpdateLastLoginAt::class);
        Event::listen(UserDeactivated::class, ReassignOpenStepsOnUserDeactivated::class);
    }

    /**
     * Register Gates and Policies (ADR-004, Story 1.3).
     */
    protected function configurePolicies(): void
    {
        // Explicit registration for User model (automatic discovery also works,
        // but explicit is safer when model namespace is non-standard)
        Gate::policy(User::class, UserPolicy::class);

        // Dashboard gate — no model equivalent (ADR-004)
        Gate::define('dashboard.view', fn (User $user): bool => $user->hasRole(['admin', 'manager', 'process_designer'])
        );
    }

    /**
     * Configure rate limiters for the application (ADR-028).
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('auth', fn ($request) => Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('notifications', fn ($request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('uploads', fn ($request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
