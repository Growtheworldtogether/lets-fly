<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title')</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
{{-- Self-contained on purpose: error pages must work when the database is down and must say nothing about the site. --}}
<style>
*{box-sizing:border-box}
:root{--gold:#d7a970;--gold-hover:#e4bd8d}
html,body{margin:0;min-height:100%;background:#000;color:#f2f2f2;font-family:'Figtree','Helvetica Neue',Arial,sans-serif;font-weight:400;line-height:1.5}
body{min-height:100vh;display:flex;flex-direction:column;background:radial-gradient(ellipse 80% 60% at 50% 0%,#1a1612 0%,#000 70%)}
.bar{height:56px;display:flex;align-items:center;padding:0 48px}
.dot{width:10px;height:10px;border:1px solid var(--gold);border-radius:50%}
main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px}
.box{width:100%;max-width:460px}
.eyebrow{font-family:'IBM Plex Mono',ui-monospace,monospace;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#8a8a8f;margin:0 0 12px}
.code{font-weight:300;font-size:clamp(72px,16vw,120px);line-height:1;color:var(--gold);margin:0 0 12px;letter-spacing:2px}
h1{font-weight:300;font-size:32px;line-height:1.2;margin:0 0 24px;padding-bottom:12px;border-bottom:1px solid #1d1d1f}
.lead{color:#b5b5ba;margin:0 0 28px}
.btn{display:inline-flex;align-items:center;height:44px;padding:0 28px;border-radius:4px;border:1px solid var(--gold);color:var(--gold);text-decoration:none;font-weight:500;font-size:14px;letter-spacing:1px;text-transform:uppercase}
.btn:hover{background:var(--gold);color:#111}
.btn:focus-visible{outline:2px solid #fff;outline-offset:2px}
@media(max-width:600px){.bar{padding:0 20px}h1{font-size:26px}}
</style>
</head>
<body>
<header class="bar" aria-hidden="true"><span class="dot"></span></header>
<main>
    <div class="box">
        <p class="eyebrow">@yield('eyebrow')</p>
        <p class="code" aria-hidden="true">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p class="lead">@yield('message')</p>
        @hasSection('action')@yield('action')@else<a class="btn" href="/">Back to the start</a>@endif
    </div>
</main>
</body>
</html>
