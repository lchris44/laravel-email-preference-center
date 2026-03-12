<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class MigrationsTest extends TestCase
{
    public function test_creates_email_preferences_table_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('email_preferences'));

        foreach (['id', 'notifiable_type', 'notifiable_id', 'category', 'frequency', 'unsubscribed_at'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('email_preferences', $column),
                "Column [{$column}] is missing from email_preferences"
            );
        }
    }

    public function test_creates_email_preference_logs_table_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('email_preference_logs'));

        foreach (['id', 'notifiable_type', 'notifiable_id', 'category', 'action', 'via', 'ip_address', 'user_agent', 'created_at'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('email_preference_logs', $column),
                "Column [{$column}] is missing from email_preference_logs"
            );
        }
    }
}
