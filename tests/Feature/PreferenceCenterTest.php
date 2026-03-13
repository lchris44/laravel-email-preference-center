<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class PreferenceCenterTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Test User']);
    }

    public function test_get_preference_center_with_valid_signature_returns_200(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $this->get($url)->assertOk();
    }

    public function test_get_preference_center_with_invalid_signature_returns_403(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user) . 'tampered';

        $this->get($url)->assertForbidden();
    }

    public function test_preference_center_shows_only_non_required_categories(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $response = $this->get($url);

        $response->assertDontSee('Security Alerts');
        $response->assertDontSee('Billing & Invoices');
        $response->assertSee('Activity Digest');
        $response->assertSee('Product Updates');
    }

    public function test_post_saves_unsubscribe(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $this->post($url, [
            'categories' => ['marketing' => '0'],
        ])->assertRedirect();

        $this->assertFalse($this->user->fresh()->prefersEmail('marketing'));
    }

    public function test_post_saves_subscribe(): void
    {
        $this->user->unsubscribe('marketing', 'api');

        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $this->post($url, [
            'categories' => ['marketing' => '1'],
        ])->assertRedirect();

        $this->assertTrue($this->user->fresh()->prefersEmail('marketing'));
    }

    public function test_post_saves_frequency(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $this->post($url, [
            'categories'  => ['digest' => '1'],
            'frequencies' => ['digest' => 'weekly'],
        ])->assertRedirect();

        $this->assertSame('weekly', $this->user->fresh()->emailFrequency('digest'));
    }

    public function test_post_required_category_cannot_be_unsubscribed(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $this->post($url, [
            'categories' => ['security' => '0'],
        ])->assertRedirect();

        $this->assertTrue($this->user->fresh()->prefersEmail('security'));
    }

    public function test_post_with_invalid_signature_returns_403(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user) . 'tampered';

        $this->post($url)->assertForbidden();
    }

    public function test_post_logs_via_as_preference_center(): void
    {
        $url = SignedUnsubscribeUrl::generateForCenter($this->user);

        $this->post($url, [
            'categories' => ['marketing' => '0'],
        ]);

        $log = $this->user->emailPreferenceLogs()->latest('id')->first();

        $this->assertSame('preference_center', $log->via);
        $this->assertSame('unsubscribed', $log->action);
    }
}
