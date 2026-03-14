<?php

namespace Lchris44\EmailPreferenceCenter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;

class PreferenceCenterController extends Controller
{
    public function __construct(protected CategoryRegistry $registry) {}

    /**
     * GET — render the preference center form.
     */
    public function show(Request $request): \Illuminate\View\View
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        $notifiable = SignedUnsubscribeUrl::resolveNotifiable(
            $request->query('notifiable_type'),
            $request->query('notifiable_id')
        );

        if (! $notifiable) {
            abort(404);
        }

        $categories = collect($this->registry->all())->map(function (array $def, string $key) use ($notifiable) {
            return [
                'key'         => $key,
                'label'       => $def['label'] ?? $key,
                'description' => $def['description'] ?? '',
                'required'    => $def['required'] ?? false,
                'frequencies' => $def['frequency'] ?? null,
                'subscribed'  => $notifiable->prefersEmail($key),
                'frequency'   => $notifiable->prefersEmail($key) ? $notifiable->emailFrequency($key) : 'never',
            ];
        })->values();

        return view('email-preferences::preference-center', [
            'notifiable' => $notifiable,
            'categories' => $categories,
            'actionUrl'  => $request->fullUrl(),
        ]);
    }

    /**
     * POST — save preferences submitted from the form.
     */
    public function save(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        $notifiable = SignedUnsubscribeUrl::resolveNotifiable(
            $request->query('notifiable_type'),
            $request->query('notifiable_id')
        );

        if (! $notifiable) {
            abort(404);
        }

        foreach ($this->registry->all() as $key => $def) {
            if ($this->registry->isRequired($key)) {
                continue;
            }

            if ($this->registry->supportsFrequency($key) && $request->filled("frequencies.{$key}")) {
                $frequency = $request->input("frequencies.{$key}");
                if (in_array($frequency, $this->registry->allowedFrequencies($key), true)) {
                    if ($frequency === 'never') {
                        $notifiable->unsubscribe($key, 'preference_center');
                    } else {
                        $notifiable->subscribe($key, 'preference_center');
                        $notifiable->setEmailFrequency($key, $frequency, 'preference_center');
                    }
                }
            } else {
                $subscribed = $request->boolean("categories.{$key}");

                if ($subscribed) {
                    $notifiable->subscribe($key, 'preference_center');
                } else {
                    $notifiable->unsubscribe($key, 'preference_center');
                }
            }
        }

        return redirect()->back()->with('saved', true);
    }
}
