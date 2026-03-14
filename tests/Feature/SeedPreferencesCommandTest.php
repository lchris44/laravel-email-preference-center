<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Lchris44\EmailPreferenceCenter\Models\EmailPreference;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class SeedPreferencesCommandTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Basic seeding
    // -------------------------------------------------------------------------

    public function test_seeds_default_preferences_for_all_users(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

        $this->artisan('email-preferences:seed', ['--model' => User::class])
            ->assertSuccessful();

        $categories = array_keys(config('email-preferences.categories'));

        $this->assertSame(
            2 * count($categories),
            EmailPreference::count()
        );
    }

    public function test_seeds_one_row_per_category_per_user(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->artisan('email-preferences:seed', ['--model' => User::class])
            ->assertSuccessful();

        $categories = array_keys(config('email-preferences.categories'));

        foreach ($categories as $category) {
            $this->assertDatabaseHas('email_preferences', [
                'notifiable_type' => User::class,
                'notifiable_id'   => $user->id,
                'category'        => $category,
            ]);
        }
    }

    public function test_uses_instant_as_default_frequency(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->artisan('email-preferences:seed', ['--model' => User::class])
            ->assertSuccessful();

        // 'digest' supports frequency — should default to 'instant'
        $this->assertDatabaseHas('email_preferences', [
            'category'  => 'digest',
            'frequency' => 'instant',
        ]);
    }

    // -------------------------------------------------------------------------
    // Custom frequency option
    // -------------------------------------------------------------------------

    public function test_seeds_with_custom_frequency(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->artisan('email-preferences:seed', [
            '--model'     => User::class,
            '--frequency' => 'weekly',
        ])->assertSuccessful();

        $this->assertDatabaseHas('email_preferences', [
            'category'  => 'digest',
            'frequency' => 'weekly',
        ]);
    }

    public function test_non_frequency_categories_always_use_instant(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        // 'marketing' has no frequency config — should always be 'instant'
        $this->artisan('email-preferences:seed', [
            '--model'     => User::class,
            '--frequency' => 'weekly',
        ])->assertSuccessful();

        $this->assertDatabaseHas('email_preferences', [
            'category'  => 'marketing',
            'frequency' => 'instant',
        ]);
    }

    // -------------------------------------------------------------------------
    // Skip existing rows (default behaviour)
    // -------------------------------------------------------------------------

    public function test_skips_existing_preferences_without_force(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        // Pre-existing preference with weekly frequency
        EmailPreference::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'category'        => 'marketing',
            'frequency'       => 'weekly',
        ]);

        $this->artisan('email-preferences:seed', ['--model' => User::class])
            ->assertSuccessful();

        // Should NOT have been overwritten to 'instant'
        $this->assertDatabaseHas('email_preferences', [
            'notifiable_id' => $user->id,
            'category'      => 'marketing',
            'frequency'     => 'weekly',
        ]);
    }

    // -------------------------------------------------------------------------
    // --force flag
    // -------------------------------------------------------------------------

    public function test_force_flag_overwrites_existing_preferences(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        EmailPreference::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'category'        => 'marketing',
            'frequency'       => 'weekly',
        ]);

        $this->artisan('email-preferences:seed', [
            '--model' => User::class,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('email_preferences', [
            'notifiable_id' => $user->id,
            'category'      => 'marketing',
            'frequency'     => 'instant',
        ]);
    }

    // -------------------------------------------------------------------------
    // No users
    // -------------------------------------------------------------------------

    public function test_succeeds_gracefully_when_no_users_exist(): void
    {
        $this->artisan('email-preferences:seed', ['--model' => User::class])
            ->assertSuccessful();

        $this->assertSame(0, EmailPreference::count());
    }

    // -------------------------------------------------------------------------
    // Invalid model
    // -------------------------------------------------------------------------

    public function test_fails_gracefully_with_invalid_model_class(): void
    {
        $this->artisan('email-preferences:seed', ['--model' => 'App\\Models\\DoesNotExist'])
            ->assertFailed();
    }
}
