<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f9fafb; }
        .card { background: #fff; border-radius: 8px; padding: 2rem 2.5rem; max-width: 420px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        h1 { font-size: 1.25rem; margin-bottom: .5rem; color: #111; }
        p { color: #555; font-size: .95rem; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>You've been unsubscribed</h1>
        <p>You will no longer receive <strong>{{ $category }}</strong> emails.</p>
    </div>
</body>
</html>
