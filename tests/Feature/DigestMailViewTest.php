<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Mail;
use Lchris44\EmailPreferenceCenter\Mail\DigestMail;
use Lchris44\EmailPreferenceCenter\Models\PendingDigestItem;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\MarketingAttributeNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

/**
 * Tests that the digest view renders correctly for both payload shapes:
 *
 * 1. Channel shape  — produced by EmailPreferenceChannel::serializeMailMessage()
 *                     keys: subject, intro_lines, outro_lines, action_text, action_url
 *
 * 2. Manual shape   — produced by a direct DigestQueue::dispatch() call
 *                     keys: title, body  (user-defined, freeform)
 */
class DigestMailViewTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    }

    // -------------------------------------------------------------------------
    // Channel payload shape
    // -------------------------------------------------------------------------

    public function test_view_renders_subject_from_channel_payload(): void
    {
        $html = $this->renderDigestWith([[
            'subject'     => 'Your order has shipped',
            'intro_lines' => ['Order #1234 is on its way.'],
        ]]);

        $this->assertStringContainsString('Your order has shipped', $html);
        $this->assertStringContainsString('Order #1234 is on its way.', $html);
    }

    public function test_view_renders_multiple_intro_lines_from_channel_payload(): void
    {
        $html = $this->renderDigestWith([[
            'subject'     => 'Weekly summary',
            'intro_lines' => ['Line one.', 'Line two.', 'Line three.'],
        ]]);

        $this->assertStringContainsString('Line one.', $html);
        $this->assertStringContainsString('Line two.', $html);
        $this->assertStringContainsString('Line three.', $html);
    }

    public function test_view_renders_action_button_from_channel_payload(): void
    {
        $html = $this->renderDigestWith([[
            'subject'     => 'Action required',
            'intro_lines' => ['Please review your account.'],
            'action_text' => 'Review Now',
            'action_url'  => 'https://example.com/review',
        ]]);

        $this->assertStringContainsString('Review Now', $html);
        $this->assertStringContainsString('https://example.com/review', $html);
    }

    public function test_view_renders_outro_lines_from_channel_payload(): void
    {
        $html = $this->renderDigestWith([[
            'subject'     => 'Update',
            'intro_lines' => ['Something happened.'],
            'outro_lines' => ['Thank you for using our service.'],
        ]]);

        $this->assertStringContainsString('Thank you for using our service.', $html);
    }

    public function test_view_omits_action_button_when_action_fields_are_absent(): void
    {
        $html = $this->renderDigestWith([[
            'subject'     => 'No action needed',
            'intro_lines' => ['Just an informational update.'],
        ]]);

        // The action button has a distinctive inline background style — absent when no action_text/action_url
        $this->assertStringNotContainsString('background: #2563eb', $html);
    }

    // -------------------------------------------------------------------------
    // Manual payload shape (DigestQueue::dispatch called directly)
    // -------------------------------------------------------------------------

    public function test_view_renders_title_from_manual_payload(): void
    {
        $html = $this->renderDigestWith([[
            'title' => 'EUR/USD breaks resistance',
            'body'  => 'Strong buy signal detected.',
        ]]);

        $this->assertStringContainsString('EUR/USD breaks resistance', $html);
        $this->assertStringContainsString('Strong buy signal detected.', $html);
    }

    public function test_view_renders_title_without_body_from_manual_payload(): void
    {
        $html = $this->renderDigestWith([[
            'title' => 'Quick heads-up',
        ]]);

        $this->assertStringContainsString('Quick heads-up', $html);
    }

    public function test_view_renders_body_without_title_from_manual_payload(): void
    {
        $html = $this->renderDigestWith([[
            'body' => 'Something notable happened today.',
        ]]);

        $this->assertStringContainsString('Something notable happened today.', $html);
    }

    // -------------------------------------------------------------------------
    // Multiple items
    // -------------------------------------------------------------------------

    public function test_view_renders_multiple_items(): void
    {
        $html = $this->renderDigestWith([
            ['subject' => 'First notification', 'intro_lines' => ['First body.']],
            ['title'   => 'Second notification', 'body' => 'Second body.'],
            ['subject' => 'Third notification', 'intro_lines' => ['Third body.']],
        ]);

        $this->assertStringContainsString('First notification', $html);
        $this->assertStringContainsString('Second notification', $html);
        $this->assertStringContainsString('Third notification', $html);
    }

    // -------------------------------------------------------------------------
    // Full end-to-end: notification channel → digest → view renders
    // -------------------------------------------------------------------------

    public function test_channel_payload_stored_and_rendered_correctly_end_to_end(): void
    {
        config(['email-preferences.categories.marketing.frequency' => ['instant', 'daily', 'weekly', 'never']]);

        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'daily');

        // Dispatch via the notification channel — this stores a channel-shape payload
        $this->user->notify(new MarketingAttributeNotification());

        $this->assertSame(1, PendingDigestItem::count());

        $item    = PendingDigestItem::first();
        $payload = $item->payload;

        // Verify the channel stored the correct keys
        $this->assertArrayHasKey('subject', $payload);
        $this->assertArrayHasKey('intro_lines', $payload);
        $this->assertSame('Monthly update', $payload['subject']);
        $this->assertContains('Here is what is new this month.', $payload['intro_lines']);

        // Render the digest view and verify the content appears
        $html = $this->renderDigestWith([$payload]);

        $this->assertStringContainsString('Monthly update', $html);
        $this->assertStringContainsString('Here is what is new this month.', $html);
    }

    public function test_full_daily_flow_via_notification_channel_renders_content(): void
    {
        Mail::fake();

        config(['email-preferences.categories.marketing.frequency' => ['instant', 'daily', 'weekly', 'never']]);

        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'daily');

        // Notify 3 times — all should be queued, none sent immediately
        $this->user->notify(new MarketingAttributeNotification());
        $this->user->notify(new MarketingAttributeNotification());
        $this->user->notify(new MarketingAttributeNotification());

        Mail::assertNothingSent();
        $this->assertSame(3, PendingDigestItem::count());

        // Trigger the daily digest — all 3 items should be sent in one email
        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Mail::assertSent(DigestMail::class, function (DigestMail $mail) {
            return $mail->hasTo($this->user->email)
                && $mail->items->count() === 3;
        });

        $this->assertSame(0, PendingDigestItem::count());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a DigestMail with fake PendingDigestItem models and render its view to HTML.
     *
     * @param  array<int, array<string, mixed>>  $payloads
     */
    private function renderDigestWith(array $payloads): string
    {
        $items = new EloquentCollection(
            array_map(fn (array $payload) => new PendingDigestItem([
                'notifiable_type' => User::class,
                'notifiable_id'   => $this->user->id,
                'category'        => 'digest',
                'frequency'       => 'daily',
                'type'            => 'test',
                'payload'         => $payload,
            ]), $payloads)
        );

        $mailable = new DigestMail($this->user, $items, 'daily');

        return $mailable->render();
    }
}
