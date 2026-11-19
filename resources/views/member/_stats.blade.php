{{-- Up to three short, number-like facts as a big-number row. Needs at least two to show. --}}
@php($pick = collect($facts ?? [])->filter(fn ($f) => preg_match('/\d/', $f[1]) && mb_strlen($f[1]) <= 15)->take(3)->values())
@if ($pick->count() >= 2)
<dl class="v-stats3">@foreach ($pick as [$k, $v])<div><dd>{{ $v }}</dd><dt>{{ $k }}</dt></div>@endforeach</dl>
@endif
