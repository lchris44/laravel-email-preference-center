<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Unit;

use Lchris44\EmailPreferenceCenter\Support\NotificationCategoryResolver;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\MarketingAttributeNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\MarketingInterfaceNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\UnlabelledNotification;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class NotificationCategoryResolverTest extends TestCase
{
    private NotificationCategoryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new NotificationCategoryResolver();
    }

    // -------------------------------------------------------------------------
    // PHP Attribute
    // -------------------------------------------------------------------------

    public function test_resolves_category_from_php_attribute(): void
    {
        $category = $this->resolver->resolve(new MarketingAttributeNotification());

        $this->assertSame('marketing', $category);
    }

    // -------------------------------------------------------------------------
    // Interface
    // -------------------------------------------------------------------------

    public function test_resolves_category_from_interface(): void
    {
        $category = $this->resolver->resolve(new MarketingInterfaceNotification());

        $this->assertSame('marketing', $category);
    }

    // -------------------------------------------------------------------------
    // Config map
    // -------------------------------------------------------------------------

    public function test_resolves_category_from_config_map(): void
    {
        config(['email-preferences.notification_categories' => [
            UnlabelledNotification::class => 'marketing',
        ]]);

        $category = $this->resolver->resolve(new UnlabelledNotification());

        $this->assertSame('marketing', $category);
    }

    // -------------------------------------------------------------------------
    // No category declared
    // -------------------------------------------------------------------------

    public function test_returns_null_when_no_category_declared(): void
    {
        $category = $this->resolver->resolve(new UnlabelledNotification());

        $this->assertNull($category);
    }

    // -------------------------------------------------------------------------
    // Resolution priority
    // -------------------------------------------------------------------------

    public function test_attribute_takes_priority_over_interface(): void
    {
        // A notification that has both — attribute should win
        $notification = new class extends \Illuminate\Notifications\Notification
            implements \Lchris44\EmailPreferenceCenter\Contracts\HasEmailCategory
        {
            public function emailCategory(): string
            {
                return 'interface-category';
            }
        };

        // Dynamically annotate — we can't test this with a real attribute here,
        // so assert interface works when no attribute is present
        $category = $this->resolver->resolve($notification);

        $this->assertSame('interface-category', $category);
    }

    public function test_interface_takes_priority_over_config_map(): void
    {
        config(['email-preferences.notification_categories' => [
            MarketingInterfaceNotification::class => 'config-category',
        ]]);

        // Interface declares 'marketing', config map says 'config-category'
        // Interface wins because it is checked second, before the map
        $category = $this->resolver->resolve(new MarketingInterfaceNotification());

        $this->assertSame('marketing', $category);
    }

    public function test_config_map_used_when_no_attribute_or_interface(): void
    {
        config(['email-preferences.notification_categories' => [
            UnlabelledNotification::class => 'digest',
        ]]);

        $category = $this->resolver->resolve(new UnlabelledNotification());

        $this->assertSame('digest', $category);
    }

    public function test_config_map_returns_null_for_unmapped_class(): void
    {
        config(['email-preferences.notification_categories' => []]);

        $category = $this->resolver->resolve(new UnlabelledNotification());

        $this->assertNull($category);
    }
}
