<?php

namespace Lchris44\EmailPreferenceCenter\Console\Commands;

use Illuminate\Console\Command;
use Lchris44\EmailPreferenceCenter\Models\EmailPreference;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;

class SeedPreferencesCommand extends Command
{
    protected $signature = 'email-preferences:seed
                            {--model=  : Fully-qualified notifiable model class (e.g. App\\Models\\User)}
                            {--frequency=instant : Default frequency for frequency-controlled categories}
                            {--force : Overwrite preferences that already exist}';

    protected $description = 'Seed default email preference rows for all existing notifiables';

    public function handle(CategoryRegistry $registry): int
    {
        $modelClass = $this->option('model') ?? $this->resolveDefaultModel();

        if (! $modelClass || ! class_exists($modelClass)) {
            $this->error("Model class [{$modelClass}] not found. Pass --model=App\\Models\\User");

            return self::FAILURE;
        }

        $defaultFrequency = $this->option('frequency');
        $force            = $this->option('force');
        $categories       = array_keys($registry->all());

        if (empty($categories)) {
            $this->warn('No categories defined in config/email-preferences.php. Nothing to seed.');

            return self::SUCCESS;
        }

        $this->info("Seeding preferences for <comment>{$modelClass}</comment>...");
        $this->newLine();

        $total   = 0;
        $skipped = 0;

        $modelClass::query()->each(function ($notifiable) use (
            $registry, $categories, $defaultFrequency, $force, &$total, &$skipped
        ) {
            foreach ($categories as $category) {
                $exists = EmailPreference::where('notifiable_type', get_class($notifiable))
                    ->where('notifiable_id', $notifiable->getKey())
                    ->where('category', $category)
                    ->exists();

                if ($exists && ! $force) {
                    $skipped++;
                    continue;
                }

                $frequency = $registry->supportsFrequency($category) ? $defaultFrequency : 'instant';

                EmailPreference::updateOrCreate(
                    [
                        'notifiable_type' => get_class($notifiable),
                        'notifiable_id'   => $notifiable->getKey(),
                        'category'        => $category,
                    ],
                    [
                        'frequency'       => $frequency,
                        'unsubscribed_at' => null,
                    ]
                );

                $total++;
            }
        });

        $this->info("Created / updated: <comment>{$total}</comment> preference rows.");

        if ($skipped > 0) {
            $this->line("Skipped (already exist): <comment>{$skipped}</comment>. Use --force to overwrite.");
        }

        return self::SUCCESS;
    }

    private function resolveDefaultModel(): ?string
    {
        // Try the most common locations
        foreach (['App\\Models\\User', 'App\\User'] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }
}
