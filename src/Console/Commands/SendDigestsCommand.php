<?php

namespace Lchris44\EmailPreferenceCenter\Console\Commands;

use Illuminate\Console\Command;
use Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend;
use Lchris44\EmailPreferenceCenter\Models\EmailPreference;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;

class SendDigestsCommand extends Command
{
    protected $signature = 'email-preferences:send-digests
                            {frequency : The frequency to dispatch (daily or weekly)}';

    protected $description = 'Fire DigestReadyToSend events for all notifiables matching the given frequency.';

    public function handle(CategoryRegistry $registry): int
    {
        $frequency = $this->argument('frequency');

        if (! in_array($frequency, ['daily', 'weekly'], true)) {
            $this->error("Frequency must be 'daily' or 'weekly', got '{$frequency}'.");
            return self::FAILURE;
        }

        $dispatched = 0;

        foreach ($registry->all() as $key => $def) {
            if (! $registry->supportsFrequency($key)) {
                continue;
            }

            EmailPreference::query()
                ->where('category', $key)
                ->where('frequency', $frequency)
                ->whereNull('unsubscribed_at')
                ->each(function (EmailPreference $preference) use ($key, $frequency, &$dispatched) {
                    $notifiable = SignedUnsubscribeUrl::resolveNotifiable(
                        $preference->notifiable_type,
                        $preference->notifiable_id
                    );

                    if (! $notifiable) {
                        return;
                    }

                    event(new DigestReadyToSend($notifiable, $key, $frequency));
                    $dispatched++;
                });
        }

        $this->info("Dispatched {$dispatched} digest event(s) for frequency '{$frequency}'.");

        return self::SUCCESS;
    }
}
