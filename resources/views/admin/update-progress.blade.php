{{-- The update's progress page: runs one short step per load, then loads itself again. Self-contained (no layout or
     stylesheet), because the site's own files are being replaced while it shows. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="1;url={{ $next }}">
    <meta name="robots" content="noindex">
    <title>Updating {{ $percent }}% | {{ brand('name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: #000; color: #d0d1d2; font: 17px/1.5 system-ui, -apple-system, 'Segoe UI', Arial, sans-serif; }
        main { width: 100%; max-width: 520px; padding: 32px; border: 1px solid #1d1d1f; border-radius: 4px; background: #0f0f10; }
        h1 { margin: 0 0 8px; color: #fff; font-weight: 300; font-size: 26px; line-height: 1.15; }
        p { margin: 0; }
        .bar { height: 12px; margin: 22px 0 10px; border-radius: 4px; background: #1d1d1f; overflow: hidden; }
        .bar i { display: block; height: 100%; width: {{ $percent }}%; background: #d7a970; }
        .num { font-size: 15px; font-weight: 700; color: #8e8f93; }
        a { color: #d7a970; font-weight: 700; }
    </style>
</head>
<body>
    <main>
        <h1>Updating {{ brand('name') }}</h1>
        <p>{{ $label }}. Please keep this page open; it carries on by itself.</p>
        <div class="bar" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><i></i></div>
        <p class="num">{{ $percent }}%</p>
        <noscript><p style="margin-top:16px"><a href="{{ $next }}">Continue</a></p></noscript>
    </main>
    <script>setTimeout(function () { window.location.replace(@json($next)); }, 400);</script>
</body>
</html>
