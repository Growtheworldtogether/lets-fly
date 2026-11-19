<nav class="subnav" aria-label="Admin">
  <a href="{{ route('admin.home') }}" @class(['on' => request()->routeIs('admin.home')])>Overview</a>
  @can('members.manage')
    <a href="{{ route('admin.invites') }}" @class(['on' => request()->routeIs('admin.invites*')])>Invites</a>
    <a href="{{ route('admin.members') }}" @class(['on' => request()->routeIs('admin.members')])>Members</a>
    <a href="{{ route('admin.funnel') }}" @class(['on' => request()->routeIs('admin.funnel')])>Funnel</a>
  @endcan
  @can('admin.manage')<a href="{{ route('admin.settings') }}" @class(['on' => request()->routeIs('admin.settings')])>Settings</a>@endcan
</nav>
