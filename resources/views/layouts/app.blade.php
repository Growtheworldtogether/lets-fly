<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title', brand('name'))</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/pioneer.css') }}">
</head>
<body class="@yield('bodyclass')">
@if (session('impersonation'))<div class="imp">Signed in as {{ session('impersonation.member_name') }} (you are {{ session('impersonation.admin_name') }}). <form method="post" action="{{ route('admin.exit') }}">@csrf<button type="submit" class="link">Exit</button></form></div>@endif
<header class="bar"><a class="mark" href="{{ auth()->check() ? (auth()->user()->isAdmin() && ! session('impersonation') ? route('admin.home') : route('vehicle')) : route('gate') }}">{{ brand('name') }}</a>@auth @unless (View::hasSection('nomenu'))<nav class="topnav" aria-label="Main">@if (! auth()->user()->isAdmin() || session('impersonation'))<a href="{{ route('vehicle') }}">The vehicle</a><a href="{{ route('dashboard') }}">My reservation</a>@else<a href="{{ route('admin.home') }}">Admin</a><a href="{{ route('vehicle') }}">Preview</a>@endif</nav><form class="bar-out" method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost" type="submit">Sign out</button></form><button class="burger" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="drawer"><span></span><span></span><span></span></button>@endunless @endauth</header>
@auth
@unless (View::hasSection('nomenu'))
<div class="scrim" hidden></div>
<aside class="drawer" id="drawer" aria-label="Menu" hidden><button class="drawer-x" type="button" aria-label="Close menu">&times;</button>@if (auth()->user()->isAdmin() && ! session('impersonation') && request()->is('admin', 'admin/*'))<p class="drawer-h">Admin</p><nav class="drawer-admin" aria-label="Admin">@include('admin._links')</nav><p class="drawer-h">Site</p>@endif<nav>@if (! auth()->user()->isAdmin() || session('impersonation'))<a href="{{ route('vehicle') }}">The vehicle</a><a href="{{ route('dashboard') }}">My reservation</a>@else<a href="{{ route('admin.home') }}">Admin</a><a href="{{ route('vehicle') }}">Preview</a>@endif</nav><form method="post" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Sign out</button></form></aside>
@endunless
@endauth
<main class="@yield('mainclass')">@yield('content')</main>
<footer class="foot">{{ brand('footer') }}</footer>
@foreach (['status', 'sent', 'error', 'toast'] as $k)@if (session($k))<div class="toast {{ $k === 'error' ? 'bad' : '' }}" role="status">{{ session($k) }}</div>@endif @endforeach
@if ($errors->any())<div class="toast bad" role="alert">{{ $errors->first() }}</div>@endif
<script>document.querySelectorAll('table.tbl').forEach(function(t){var th=[].map.call(t.querySelectorAll('thead th'),function(h){return h.textContent.trim()});if(!th.length)return;t.classList.add('stack-rows');t.querySelectorAll('tbody tr').forEach(function(r){[].forEach.call(r.children,function(c,i){if(th[i])c.setAttribute('data-label',th[i])})})});</script>
<script>(function(){var h=document.querySelector('.bar');function sc(){document.body.classList.toggle('scrolled',scrollY>40)}addEventListener('scroll',sc,{passive:true});sc()})();(function(){var b=document.body,d=document.getElementById('drawer'),s=document.querySelector('.scrim'),t=document.querySelector('.burger');if(!d||!t)return;function set(o){d.hidden=s.hidden=!o;b.classList.toggle('menu-open',o);t.setAttribute('aria-expanded',o?'true':'false');if(o)d.querySelector('a,button').focus();else t.focus()}t.addEventListener('click',function(){set(true)});s.addEventListener('click',function(){set(false)});d.querySelector('.drawer-x').addEventListener('click',function(){set(false)});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!d.hidden)set(false)});d.addEventListener('click',function(e){if(e.target.closest('a'))set(false)});window.addEventListener('resize',function(){if(innerWidth>860&&!d.hidden)set(false)})})();document.querySelectorAll('.toast').forEach(function(t,i){setTimeout(function(){t.remove()},5000+i*500)});</script>
</body>
</html>
