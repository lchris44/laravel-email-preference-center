<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Unit;

use Lchris44\EmailPreferenceCenter\Models\EmailPreference;
use Lchris44\EmailPreferenceCenter\Models\EmailPreferenceLog;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class HasEmailPreferencesTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Lenos']);
    }

    // ------------------------------------------------------------------
    // prefersEmail
    // ------------------------------------------------------------------

    public function test_prefers_email_returns_true_by_default(): void
    {
        $this->assertTrue($this->user->prefersEmail('marketing'));
    }

    public function test_prefers_email_always_returns_true_for_required_categories(): void
    {
        $this->user->unsubscribe('security');

        $this->assertTrue($this->user->prefersEmail('security'));
    }

    public function test_prefers_email_returns_false_after_unsubscribe(): void
    {
        $this->user->unsubscribe('marketing');

        $this->assertFalse($this->user->prefersEmail('marketing'));
    }

    public function test_prefers_email_returns_true_after_resubscribe(): void
    {
        $this->user->unsubscribe('marketing');
        $this->user->subscribe('marketing');

        $this->assertTrue($this->user->prefersEmail('marketing'));
    }

    public function test_prefers_email_returns_false_when_frequency_is_never(): void
    {
        $this->user->setEmailFrequency('digest', 'never');

        $this->assertFalse($this->user->prefersEmail('digest'));
    }

    // ------------------------------------------------------------------
    // subscribe / unsubscribe
    // ------------------------------------------------------------------

    public function test_subscribe_creates_preference_row(): void
    {
        $this->user->subscribe('marketing');

        $this->assertDatabaseHas('email_preferences', [
            'category'        => 'marketing',
            'unsubscribed_at' => null,
        ]);
    }

    public function test_unsubscribe_sets_unsubscribed_at(): void
    {
        $this->user->unsubscribe('marketing');

        $preference = EmailPreference::forCategory('marketing')->first();

        $this->assertNotNull($preference->unsubscribed_at);
    }

    public function test_subscribe_does_not_duplicate_log_if_already_subscribed(): void
    {
        $this->user->subscribe('marketing');
        $this->user->subscribe('marketing'); // second call — no change

        $this->assertSame(1, $this->user->emailPreferenceLogs()->forCategory('marketing')->count());
    }

    public function test_unsubscribe_does_not_duplicate_log_if_already_unsubscribed(): void
    {
        $this->user->unsubscribe('marketing');
        $this->user->unsubscribe('marketing'); // second call — no change

        $this->assertSame(1, $this->user->emailPreferenceLogs()->forCategory('marketing')->count());
    }

    // ------------------------------------------------------------------
    // frequency
    // ------------------------------------------------------------------

    public function test_set_email_frequency_updates_the_row(): void
    {
        $this->user->setEmailFrequency('digest', 'weekly');

        $this->assertDatabaseHas('email_preferences', [
            'category'  => 'digest',
            'frequency' => 'weekly',
        ]);
    }

    public function test_set_email_frequency_throws_for_invalid_frequency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->user->setEmailFrequency('digest', 'monthly');
    }

    public function test_email_frequency_returns_instant_by_default(): void
    {
        $this->assertSame('instant', $this->user->emailFrequency('digest'));
    }

    // ------------------------------------------------------------------
    // consent log
    // ------------------------------------------------------------------

    public function test_subscribe_logs_the_action(): void
    {
        $this->user->unsubscribe('marketing');
        $this->user->subscribe('marketing', 'preference_center');

        $log = $this->user->emailPreferenceLogs()
            ->forCategory('marketing')
            ->action('subscribed')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('preference_center', $log->via);
    }

    public function test_unsubscribe_logs_the_action(): void
    {
        $this->user->unsubscribe('marketing', 'unsubscribe_link');

        $log = $this->user->emailPreferenceLogs()
            ->forCategory('marketing')
            ->action('unsubscribed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('unsubscribe_link', $log->via);
    }

    public function test_frequency_change_logs_the_action(): void
    {
        $this->user->setEmailFrequency('digest', 'weekly');

        $log = $this->user->emailPreferenceLogs()
            ->forCategory('digest')
            ->action('frequency_changed')
            ->first();

        $this->assertNotNull($log);
    }

    public function test_was_subscribed_to_returns_false_with_no_history(): void
    {
        $this->assertFalse($this->user->wasSubscribedTo('marketing', '2026-01-01'));
    }

    public function test_was_subscribed_to_returns_correct_value_for_date(): void
    {
        \Carbon\Carbon::setTestNow('2026-01-01 10:00:00');
        $this->user->subscribe('marketing');

        \Carbon\Carbon::setTestNow('2026-02-01 10:00:00');
        $this->user->unsubscribe('marketing');

        \Carbon\Carbon::setTestNow(null);

        $this->assertFalse($this->user->wasSubscribedTo('marketing', '2025-12-31'));  // before any action
        $this->assertTrue($this->user->wasSubscribedTo('marketing', '2026-01-15'));   // after subscribe
        $this->assertFalse($this->user->wasSubscribedTo('marketing', '2026-02-15')); // after unsubscribe
    }

    public function test_last_consent_for_returns_most_recent_log(): void
    {
        $this->user->subscribe('marketing');
        $this->user->unsubscribe('marketing');

        $log = $this->user->lastConsentFor('marketing');

        $this->assertInstanceOf(EmailPreferenceLog::class, $log);
        $this->assertSame('unsubscribed', $log->action);
    }
}
