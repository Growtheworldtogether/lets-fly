<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title', brand('name'))</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/pioneer.css') }}">
</head>
<body>
@if (session('impersonation'))<div class="imp">Signed in as {{ session('impersonation.member_name') }} (you are {{ session('impersonation.admin_name') }}). <form method="post" action="{{ route('admin.exit') }}">@csrf<button type="submit" class="link">Exit</button></form></div>@endif
<header class="bar"><a class="mark" href="{{ route('gate') }}">{{ brand('name') }}</a>@auth<form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost" type="submit">Sign out</button></form>@endauth</header>
<main class="@yield('mainclass')">@yield('content')</main>
<footer class="foot">{{ brand('footer') }}</footer>
@foreach (['status', 'sent', 'error', 'toast'] as $k)@if (session($k))<div class="toast {{ $k === 'error' ? 'bad' : '' }}" role="status">{{ session($k) }}</div>@endif @endforeach
@if ($errors->any())<div class="toast bad" role="alert">{{ $errors->first() }}</div>@endif
<script>document.querySelectorAll('.toast').forEach(function(t,i){setTimeout(function(){t.remove()},5000+i*500)});</script>
</body>
</html>
