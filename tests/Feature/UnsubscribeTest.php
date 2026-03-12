<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Support\Facades\URL;
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class UnsubscribeTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Lenos']);
    }

    public function test_signed_url_is_generated_for_a_notifiable_and_category(): void
    {
        $url = SignedUnsubscribeUrl::generate($this->user, 'marketing');

        $this->assertStringContainsString('email-preferences/unsubscribe', $url);
        $this->assertStringContainsString('category=marketing', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_get_unsubscribe_with_valid_signature_unsubscribes_user(): void
    {
        $url = SignedUnsubscribeUrl::generate($this->user, 'marketing');

        $response = $this->get($url);

        $response->assertStatus(200);
        $this->assertFalse($this->user->fresh()->prefersEmail('marketing'));
    }

    public function test_get_unsubscribe_with_invalid_signature_returns_403(): void
    {
        $url = route('email-preferences.unsubscribe', [
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'marketing',
        ]);

        $response = $this->get($url);

        $response->assertStatus(403);
    }

    public function test_post_unsubscribe_one_click_unsubscribes_user(): void
    {
        $url = SignedUnsubscribeUrl::generate($this->user, 'marketing');

        $response = $this->post($url, ['List-Unsubscribe' => 'One-Click']);

        $response->assertStatus(200);
        $this->assertFalse($this->user->fresh()->prefersEmail('marketing'));
    }

    public function test_unsubscribe_logs_via_as_unsubscribe_link(): void
    {
        $url = SignedUnsubscribeUrl::generate($this->user, 'marketing');

        $this->get($url);

        $log = $this->user->emailPreferenceLogs()->forCategory('marketing')->first();

        $this->assertNotNull($log);
        $this->assertSame('unsubscribe_link', $log->via);
    }

    public function test_signed_url_resolves_notifiable(): void
    {
        $resolved = SignedUnsubscribeUrl::resolveNotifiable(User::class, $this->user->id);

        $this->assertNotNull($resolved);
        $this->assertSame($this->user->id, $resolved->id);
    }

    public function test_signed_url_returns_null_for_unknown_notifiable(): void
    {
        $resolved = SignedUnsubscribeUrl::resolveNotifiable(User::class, 99999);

        $this->assertNull($resolved);
    }
}
