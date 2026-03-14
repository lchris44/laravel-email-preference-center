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
            @php $payload = is_array($item->payload) ? $item->payload : []; @endphp

            <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 8px; background: #f8fafc; border-left: 3px solid #2563eb;">
                @if(!empty($payload['title']))
                    <div style="font-weight: 600; font-size: .95rem; margin-bottom: .3rem;">{{ $payload['title'] }}</div>
                @endif
                @if(!empty($payload['body']))
                    <div style="font-size: .875rem; color: #444; line-height: 1.5;">{{ $payload['body'] }}</div>
                @endif
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
