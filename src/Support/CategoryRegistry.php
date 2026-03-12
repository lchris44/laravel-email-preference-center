<?php

namespace Lchris44\EmailPreferenceCenter\Support;

use InvalidArgumentException;

class CategoryRegistry
{
    /** @var array<string, array<string, mixed>> */
    protected array $categories = [];

    public function __construct()
    {
        $this->categories = config('email-preferences.categories', []);
    }

    public function all(): array
    {
        return $this->categories;
    }

    public function get(string $key): array
    {
        if (! $this->exists($key)) {
            throw new InvalidArgumentException("Email preference category [{$key}] is not defined.");
        }

        return $this->categories[$key];
    }

    public function exists(string $key): bool
    {
        return isset($this->categories[$key]);
    }

    public function isRequired(string $key): bool
    {
        return (bool) ($this->get($key)['required'] ?? false);
    }

    public function supportsFrequency(string $key): bool
    {
        return isset($this->get($key)['frequency']);
    }

    public function allowedFrequencies(string $key): array
    {
        return $this->get($key)['frequency'] ?? ['instant'];
    }

    public function label(string $key): string
    {
        return $this->get($key)['label'] ?? $key;
    }

    public function description(string $key): string
    {
        return $this->get($key)['description'] ?? '';
    }
}
