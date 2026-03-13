<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class SendDigestsCommandTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Test User']);
    }

    public function test_dispatches_event_for_daily_frequency(): void
    {
        Event::fake();

        $this->user->setEmailFrequency('digest', 'daily');

        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Event::assertDispatched(DigestReadyToSend::class, function (DigestReadyToSend $event) {
            return $event->notifiable->id === $this->user->id
                && $event->category === 'digest'
                && $event->frequency === 'daily';
        });
    }

    public function test_dispatches_event_for_weekly_frequency(): void
    {
        Event::fake();

        $this->user->setEmailFrequency('digest', 'weekly');

        $this->artisan('email-preferences:send-digests weekly')->assertSuccessful();

        Event::assertDispatched(DigestReadyToSend::class, function (DigestReadyToSend $event) {
            return $event->notifiable->id === $this->user->id
                && $event->frequency === 'weekly';
        });
    }

    public function test_does_not_dispatch_for_instant_frequency(): void
    {
        Event::fake();

        // instant is the default — no preference row needed
        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Event::assertNotDispatched(DigestReadyToSend::class);
    }

    public function test_does_not_dispatch_for_unsubscribed_user(): void
    {
        Event::fake();

        $this->user->setEmailFrequency('digest', 'daily');
        $this->user->unsubscribe('digest');

        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Event::assertNotDispatched(DigestReadyToSend::class);
    }

    public function test_does_not_dispatch_for_never_frequency(): void
    {
        Event::fake();

        $this->user->setEmailFrequency('digest', 'never');

        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Event::assertNotDispatched(DigestReadyToSend::class);
    }

    public function test_dispatches_for_multiple_users(): void
    {
        Event::fake();

        $userTwo = User::create(['name' => 'User Two']);

        $this->user->setEmailFrequency('digest', 'daily');
        $userTwo->setEmailFrequency('digest', 'daily');

        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Event::assertDispatchedTimes(DigestReadyToSend::class, 2);
    }

    public function test_returns_failure_for_invalid_frequency(): void
    {
        $this->artisan('email-preferences:send-digests monthly')->assertFailed();
    }

    public function test_does_not_dispatch_for_wrong_frequency(): void
    {
        Event::fake();

        $this->user->setEmailFrequency('digest', 'weekly');

        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Event::assertNotDispatched(DigestReadyToSend::class);
    }
}
