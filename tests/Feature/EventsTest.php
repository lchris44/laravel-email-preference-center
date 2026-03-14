<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Lchris44\EmailPreferenceCenter\Events\DigestQueued;
use Lchris44\EmailPreferenceCenter\Events\DigestSent;
use Lchris44\EmailPreferenceCenter\Events\PreferenceUpdated;
use Lchris44\EmailPreferenceCenter\Events\UserUnsubscribed;
use Lchris44\EmailPreferenceCenter\Models\PendingDigestItem;
use Lchris44\EmailPreferenceCenter\Support\DigestQueue;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;
use Illuminate\Support\Facades\Mail;

class EventsTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
    }

    // -------------------------------------------------------------------------
    // PreferenceUpdated
    // -------------------------------------------------------------------------

    public function test_preference_updated_fired_on_subscribe(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->subscribe('marketing');

        Event::assertDispatched(PreferenceUpdated::class, function (PreferenceUpdated $e) {
            return $e->notifiable->id === $this->user->id
                && $e->category === 'marketing'
                && $e->action === 'subscribed'
                && $e->via === 'api';
        });
    }

    public function test_preference_updated_fired_on_unsubscribe(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->unsubscribe('marketing');

        Event::assertDispatched(PreferenceUpdated::class, function (PreferenceUpdated $e) {
            return $e->action === 'unsubscribed' && $e->category === 'marketing';
        });
    }

    public function test_preference_updated_fired_on_frequency_change(): void
    {
        Event::fake([PreferenceUpdated::class]);

        config(['email-preferences.categories.digest.frequency' => ['instant', 'daily', 'weekly', 'never']]);

        $this->user->setEmailFrequency('digest', 'weekly');

        Event::assertDispatched(PreferenceUpdated::class, function (PreferenceUpdated $e) {
            return $e->action === 'frequency_changed'
                && $e->category === 'digest'
                && $e->via === 'api';
        });
    }

    public function test_preference_updated_carries_custom_via(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->subscribe('marketing', 'admin');

        Event::assertDispatched(PreferenceUpdated::class, function (PreferenceUpdated $e) {
            return $e->via === 'admin';
        });
    }

    public function test_preference_updated_not_fired_when_already_subscribed(): void
    {
        $this->user->subscribe('marketing');

        Event::fake([PreferenceUpdated::class]);

        // Second subscribe on an already-subscribed user should be a no-op
        $this->user->subscribe('marketing');

        Event::assertNotDispatched(PreferenceUpdated::class);
    }

    public function test_preference_updated_not_fired_when_already_unsubscribed(): void
    {
        $this->user->unsubscribe('marketing');

        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->unsubscribe('marketing');

        Event::assertNotDispatched(PreferenceUpdated::class);
    }

    // -------------------------------------------------------------------------
    // UserUnsubscribed
    // -------------------------------------------------------------------------

    public function test_user_unsubscribed_fired_on_unsubscribe(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->unsubscribe('marketing', 'unsubscribe_link');

        Event::assertDispatched(UserUnsubscribed::class, function (UserUnsubscribed $e) {
            return $e->notifiable->id === $this->user->id
                && $e->category === 'marketing'
                && $e->via === 'unsubscribe_link';
        });
    }

    public function test_user_unsubscribed_not_fired_on_subscribe(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->subscribe('marketing');

        Event::assertNotDispatched(UserUnsubscribed::class);
    }

    public function test_user_unsubscribed_not_fired_for_required_categories(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        // 'security' is required — unsubscribe() is a no-op
        $this->user->unsubscribe('security');

        Event::assertNotDispatched(UserUnsubscribed::class);
        Event::assertNotDispatched(PreferenceUpdated::class);
    }

    public function test_both_events_fired_on_unsubscribe(): void
    {
        Event::fake([PreferenceUpdated::class, UserUnsubscribed::class]);

        $this->user->unsubscribe('marketing');

        Event::assertDispatched(PreferenceUpdated::class);
        Event::assertDispatched(UserUnsubscribed::class);
    }

    // -------------------------------------------------------------------------
    // DigestQueued
    // -------------------------------------------------------------------------

    public function test_digest_queued_fired_for_daily_frequency(): void
    {
        Event::fake([DigestQueued::class]);

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'daily');

        DigestQueue::dispatch($this->user, 'digest', 'comment_activity', ['title' => 'Test']);

        Event::assertDispatched(DigestQueued::class, function (DigestQueued $e) {
            return $e->notifiable->id === $this->user->id
                && $e->category === 'digest'
                && $e->frequency === 'daily'
                && $e->type === 'comment_activity';
        });
    }

    public function test_digest_queued_fired_for_weekly_frequency(): void
    {
        Event::fake([DigestQueued::class]);

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'weekly');

        DigestQueue::dispatch($this->user, 'digest', 'comment_activity', ['title' => 'Test']);

        Event::assertDispatched(DigestQueued::class, function (DigestQueued $e) {
            return $e->frequency === 'weekly';
        });
    }

    public function test_digest_queued_not_fired_for_instant_frequency(): void
    {
        Mail::fake(); // prevent SendDigestListener from hitting a real mail server
        Event::fake([DigestQueued::class]);

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'instant');

        DigestQueue::dispatch($this->user, 'digest', 'comment_activity', ['title' => 'Test']);

        Event::assertNotDispatched(DigestQueued::class);
    }

    public function test_digest_queued_not_fired_when_user_unsubscribed(): void
    {
        Event::fake([DigestQueued::class]);

        $this->user->unsubscribe('digest');

        DigestQueue::dispatch($this->user, 'digest', 'comment_activity', ['title' => 'Test']);

        Event::assertNotDispatched(DigestQueued::class);
    }

    // -------------------------------------------------------------------------
    // DigestSent
    // -------------------------------------------------------------------------

    public function test_digest_sent_fired_after_digest_mail_sent(): void
    {
        Mail::fake();
        Event::fake([DigestSent::class]);

        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Test'],
        ]);

        $listener = new \Lchris44\EmailPreferenceCenter\Listeners\SendDigestListener();
        $listener->handle(new \Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend(
            $this->user, 'digest', 'daily'
        ));

        Event::assertDispatched(DigestSent::class, function (DigestSent $e) {
            return $e->notifiable->id === $this->user->id
                && $e->category === 'digest'
                && $e->frequency === 'daily'
                && $e->itemCount === 1;
        });
    }

    public function test_digest_sent_carries_correct_item_count(): void
    {
        Mail::fake();
        Event::fake([DigestSent::class]);

        foreach (range(1, 3) as $i) {
            PendingDigestItem::create([
                'notifiable_type' => User::class,
                'notifiable_id'   => $this->user->id,
                'category'        => 'digest',
                'frequency'       => 'daily',
                'type'            => 'alert',
                'payload'         => ['title' => "Item {$i}"],
            ]);
        }

        $listener = new \Lchris44\EmailPreferenceCenter\Listeners\SendDigestListener();
        $listener->handle(new \Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend(
            $this->user, 'digest', 'daily'
        ));

        Event::assertDispatched(DigestSent::class, function (DigestSent $e) {
            return $e->itemCount === 3;
        });
    }

    public function test_digest_sent_not_fired_when_no_pending_items(): void
    {
        Mail::fake();
        Event::fake([DigestSent::class]);

        $listener = new \Lchris44\EmailPreferenceCenter\Listeners\SendDigestListener();
        $listener->handle(new \Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend(
            $this->user, 'digest', 'daily'
        ));

        Event::assertNotDispatched(DigestSent::class);
    }
}
