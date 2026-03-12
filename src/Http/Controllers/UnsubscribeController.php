<?php

namespace Lchris44\EmailPreferenceCenter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;

class UnsubscribeController extends Controller
{
    /**
     * GET — user clicked unsubscribe link in email.
     * Unsubscribes and shows a confirmation view.
     */
    public function show(Request $request): \Illuminate\View\View|\Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired unsubscribe link.');
        }

        $notifiable = SignedUnsubscribeUrl::resolveNotifiable(
            $request->query('notifiable_type'),
            $request->query('notifiable_id')
        );

        if (! $notifiable) {
            abort(404);
        }

        $category = $request->query('category');

        $notifiable->unsubscribe($category, 'unsubscribe_link');

        return response()->view('email-preferences::unsubscribe', [
            'category' => $category,
        ]);
    }

    /**
     * POST — Gmail/Yahoo one-click unsubscribe callback.
     * Called by the mail client in the background with body:
     * List-Unsubscribe=One-Click
     *
     * Must return 200 with no redirect.
     */
    public function handle(Request $request): \Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $notifiable = SignedUnsubscribeUrl::resolveNotifiable(
            $request->query('notifiable_type'),
            $request->query('notifiable_id')
        );

        if (! $notifiable) {
            abort(404);
        }

        $notifiable->unsubscribe($request->query('category'), 'unsubscribe_link');

        return response('', 200);
    }
}
