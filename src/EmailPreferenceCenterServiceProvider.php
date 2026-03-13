<?php

namespace Lchris44\EmailPreferenceCenter;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lchris44\EmailPreferenceCenter\Console\Commands\SendDigestsCommand;
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
        $this->registerCommands();
        $this->registerSchedule();
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'email-preferences');
    }

    protected function registerRoutes(): void
    {
        if (! config('email-preferences.dashboard.enabled', true)) {
            return;
        }

        $middleware = config('email-preferences.dashboard.middleware', ['web']);

        Route::middleware($middleware)->group(__DIR__ . '/../routes/web.php');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SendDigestsCommand::class]);
        }
    }

    protected function registerSchedule(): void
    {
        if (! config('email-preferences.auto_schedule', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $daily  = config('email-preferences.digest_schedules.daily', '0 8 * * *');
            $weekly = config('email-preferences.digest_schedules.weekly', '0 8 * * 1');

            $schedule->command('email-preferences:send-digests daily')->cron($daily);
            $schedule->command('email-preferences:send-digests weekly')->cron($weekly);
        });
    }

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
