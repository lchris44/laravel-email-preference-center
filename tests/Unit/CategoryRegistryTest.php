<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Unit;

use InvalidArgumentException;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class CategoryRegistryTest extends TestCase
{
    public function test_loads_categories_from_config(): void
    {
        $registry = app(CategoryRegistry::class);

        $this->assertIsArray($registry->all());
        $this->assertNotEmpty($registry->all());
    }

    public function test_returns_a_category_by_key(): void
    {
        $registry = app(CategoryRegistry::class);

        $category = $registry->get('security');

        $this->assertSame('Security Alerts', $category['label']);
    }

    public function test_throws_on_unknown_category(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CategoryRegistry::class)->get('nonexistent');
    }

    public function test_correctly_identifies_required_categories(): void
    {
        $registry = app(CategoryRegistry::class);

        $this->assertTrue($registry->isRequired('security'));
        $this->assertTrue($registry->isRequired('billing'));
        $this->assertFalse($registry->isRequired('marketing'));
    }

    public function test_correctly_identifies_categories_that_support_frequency(): void
    {
        $registry = app(CategoryRegistry::class);

        $this->assertTrue($registry->supportsFrequency('digest'));
        $this->assertFalse($registry->supportsFrequency('marketing'));
    }

    public function test_returns_allowed_frequencies_for_a_category(): void
    {
        $registry = app(CategoryRegistry::class);

        $frequencies = $registry->allowedFrequencies('digest');

        $this->assertContains('instant', $frequencies);
        $this->assertContains('daily', $frequencies);
        $this->assertContains('weekly', $frequencies);
        $this->assertContains('never', $frequencies);
    }
}
