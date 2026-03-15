<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $frequency === 'weekly' ? 'Weekly' : ($frequency === 'daily' ? 'Daily' : '') }} Digest</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f5f7; margin: 0; padding: 2rem 1rem; color: #1a1a1a;">

    <div style="max-width: 580px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 2rem; box-shadow: 0 1px 4px rgba(0,0,0,.08);">

        <div style="margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.2rem; font-weight: 600; margin: 0 0 .4rem;">
                @if($frequency === 'weekly') Weekly Digest
                @elseif($frequency === 'daily') Daily Digest
                @else Latest Update
                @endif
            </h1>
            <p style="color: #666; font-size: .875rem; margin: 0;">Hi {{ $notifiable->name ?? 'there' }}, here's your latest update.</p>
        </div>

        @foreach($items as $item)
            @php
                $payload = is_array($item->payload) ? $item->payload : [];

                // Support both payload shapes:
                // - Channel shape: subject, intro_lines, outro_lines, action_text, action_url
                // - Manual shape:  title, body
                $heading     = $payload['subject']     ?? $payload['title'] ?? null;
                $introLines  = $payload['intro_lines'] ?? (isset($payload['body']) ? [$payload['body']] : []);
                $outroLines  = $payload['outro_lines'] ?? [];
                $actionText  = $payload['action_text'] ?? null;
                $actionUrl   = $payload['action_url']  ?? null;
            @endphp

            <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 8px; background: #f8fafc; border-left: 3px solid #2563eb;">
                @if($heading)
                    <div style="font-weight: 600; font-size: .95rem; margin-bottom: .5rem;">{{ $heading }}</div>
                @endif

                @foreach($introLines as $line)
                    <div style="font-size: .875rem; color: #444; line-height: 1.5; margin-bottom: .25rem;">{{ $line }}</div>
                @endforeach

                @if($actionText && $actionUrl)
                    <a href="{{ $actionUrl }}" style="display: inline-block; margin-top: .75rem; padding: .4rem .9rem; background: #2563eb; color: #fff; border-radius: 5px; font-size: .8rem; font-weight: 600; text-decoration: none;">
                        {{ $actionText }}
                    </a>
                @endif

                @foreach($outroLines as $line)
                    <div style="font-size: .8rem; color: #888; line-height: 1.5; margin-top: .5rem;">{{ $line }}</div>
                @endforeach
            </div>
        @endforeach

        <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid #f0f0f0;">
            <p style="font-size: .75rem; color: #999; margin: 0;">
                You're receiving this because you subscribed to {{ $category }} emails.
                <a href="{!! $unsubscribeUrl !!}" style="color: #999;">Unsubscribe</a>
                &nbsp;·&nbsp;
                <a href="{!! $preferenceCenterUrl !!}" style="color: #999;">Manage preferences</a>
            </p>
        </div>

    </div>

</body>
</html>
