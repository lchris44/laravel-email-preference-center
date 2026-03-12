<?php

namespace Lchris44\EmailPreferenceCenter;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;

class EmailPreferenceCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/email-preferences.php',
            'email-preferences'
        );

        $this->app->singleton(CategoryRegistry::class, fn () => new CategoryRegistry());

        $this->app->singleton(
            EmailPreferenceCenterManager::class,
            fn ($app) => new EmailPreferenceCenterManager($app->make(CategoryRegistry::class))
        );

        $this->app->alias(EmailPreferenceCenterManager::class, 'email-preferences');
    }

    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerViews();
        $this->registerRoutes();
    }

    // ------------------------------------------------------------------
    // Views
    // ------------------------------------------------------------------

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'email-preferences');
    }

    // ------------------------------------------------------------------
    // Routes
    // ------------------------------------------------------------------

    protected function registerRoutes(): void
    {
        if (! config('email-preferences.dashboard.enabled', true)) {
            return;
        }

        $middleware = config('email-preferences.dashboard.middleware', ['web']);

        Route::middleware($middleware)->group(__DIR__ . '/../routes/web.php');
    }

    // ------------------------------------------------------------------
    // Publishables
    // ------------------------------------------------------------------

    protected function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/email-preferences.php' => config_path('email-preferences.php'),
        ], 'email-preferences-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'email-preferences-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views/' => resource_path('views/vendor/email-preferences'),
        ], 'email-preferences-views');

        $this->publishes([
            __DIR__ . '/../routes/web.php' => base_path('routes/email-preferences.php'),
        ], 'email-preferences-routes');

        $this->publishes([
            __DIR__ . '/../config/email-preferences.php'  => config_path('email-preferences.php'),
            __DIR__ . '/../database/migrations/'           => database_path('migrations'),
        ], 'email-preferences');
    }
}
