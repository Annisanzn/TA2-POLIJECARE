<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\UserReportPolicy;
use App\Models\Complaint;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Complaint::class => UserReportPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Additional gates for user-specific operations
        Gate::define('access-user-dashboard', function ($user) {
            return $user->role === 'user';
        });

        Gate::define('view-own-reports', function ($user, $report) {
            return $user->id === $report->user_id;
        });
    }
}
