<?php

namespace Lchris44\EmailPreferenceCenter\Tests;

use Lchris44\EmailPreferenceCenter\EmailPreferenceCenterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EmailPreferenceCenterServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'EmailPreferences' => \Lchris44\EmailPreferenceCenter\Facades\EmailPreferences::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Minimal users table for test fixtures
        \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name')->default('Test User');
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
