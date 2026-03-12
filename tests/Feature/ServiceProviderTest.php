<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Lchris44\EmailPreferenceCenter\EmailPreferenceCenterManager;
use Lchris44\EmailPreferenceCenter\Facades\EmailPreferences;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_registers_the_manager_singleton(): void
    {
        $this->assertInstanceOf(EmailPreferenceCenterManager::class, app(EmailPreferenceCenterManager::class));
        $this->assertInstanceOf(EmailPreferenceCenterManager::class, app('email-preferences'));
    }

    public function test_registers_the_category_registry_singleton(): void
    {
        $this->assertInstanceOf(CategoryRegistry::class, app(CategoryRegistry::class));
    }

    public function test_resolves_categories_via_the_facade(): void
    {
        $this->assertInstanceOf(CategoryRegistry::class, EmailPreferences::categories());
    }

    public function test_publishes_config_with_correct_keys(): void
    {
        $this->assertIsArray(config('email-preferences'));
        $this->assertNotEmpty(config('email-preferences.categories'));
        $this->assertSame('email_preferences', config('email-preferences.table_names.preferences'));
        $this->assertSame('email_preference_logs', config('email-preferences.table_names.logs'));
        $this->assertGreaterThan(0, config('email-preferences.signed_url_expiry_days'));
    }
}
