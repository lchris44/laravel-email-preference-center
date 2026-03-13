<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Preferences</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f4f5f7;
            margin: 0;
            padding: 2rem 1rem;
            color: #1a1a1a;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            max-width: 560px;
            margin: 0 auto;
            padding: 2rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }

        h1 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 .35rem;
        }

        .subtitle {
            color: #666;
            font-size: .9rem;
            margin: 0 0 2rem;
        }

        .category {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .category:last-of-type {
            border-bottom: none;
        }

        .category-info { flex: 1; }

        .category-label {
            font-weight: 500;
            font-size: .95rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .category-desc {
            font-size: .85rem;
            color: #666;
            margin-top: .2rem;
        }

        .category-controls {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .5rem;
            min-width: 80px;
        }

        /* Toggle */
        .toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .toggle input { display: none; }

        .toggle-track {
            position: absolute;
            inset: 0;
            background: #d1d5db;
            border-radius: 9999px;
            cursor: pointer;
            transition: background .2s;
        }

        .toggle input:checked ~ .toggle-track { background: #2563eb; }
        .toggle input:disabled ~ .toggle-track { opacity: .5; cursor: not-allowed; }

        .toggle-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 18px;
            height: 18px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            pointer-events: none;
        }

        .toggle input:checked ~ .toggle-track .toggle-thumb {
            transform: translateX(20px);
        }

        /* Frequency select */
        .frequency-select {
            font-size: .8rem;
            padding: .25rem .5rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            color: #1a1a1a;
            cursor: pointer;
        }

        /* Save button */
        .actions {
            margin-top: 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-save {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: .6rem 1.4rem;
            border-radius: 7px;
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s;
        }

        .btn-save:hover { background: #1d4ed8; }

        .saved-msg {
            font-size: .875rem;
            color: #16a34a;
        }
    </style>
</head>
<body>
    <div class="card">

        <h1>Email Preferences</h1>
        <p class="subtitle">Choose which emails you'd like to receive.</p>

        @if(session('saved'))
            <p class="saved-msg" style="margin-bottom:1.25rem;">Your preferences have been saved.</p>
        @endif

        <form method="POST" action="{{ $actionUrl }}">
            @csrf

            @foreach($categories as $cat)
                @if($cat['required']) @continue @endif
            <div class="category">
                <div class="category-info">
                    <div class="category-label">{{ $cat['label'] }}</div>
                    @if($cat['description'])
                        <div class="category-desc">{{ $cat['description'] }}</div>
                    @endif
                </div>

                <div class="category-controls">
                    @if($cat['frequencies'])
                        {{-- Frequency dropdown replaces toggle; "never" = unsubscribed --}}
                        <select
                            class="frequency-select"
                            name="frequencies[{{ $cat['key'] }}]"
                        >
                            @foreach($cat['frequencies'] as $freq)
                                <option value="{{ $freq }}" @selected($cat['frequency'] === $freq)>
                                    {{ ucfirst($freq) }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        {{-- Simple on/off toggle --}}
                        <label class="toggle">
                            <input
                                type="checkbox"
                                name="categories[{{ $cat['key'] }}]"
                                value="1"
                                @checked($cat['subscribed'])
                            >
                            <div class="toggle-track">
                                <div class="toggle-thumb"></div>
                            </div>
                        </label>
                    @endif
                </div>
            </div>
            @endforeach

            <div class="actions">
                <button type="submit" class="btn-save">Save preferences</button>
            </div>
        </form>
    </div>


</body>
</html>
