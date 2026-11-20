{{-- The escrow wire block for one reservation: $r --}}
<h2>How to pay</h2>
<div class="panel">
  <p>Send each payment by wire transfer to the escrow account below. Funds are held by {{ \App\Services\Escrow::agent() }}, not in {{ brand('name') }}'s operating account. Use your reservation code as the reference, exactly as shown.</p>
  <dl class="wire-lines">
    @foreach (\App\Services\Escrow::lines($r) as [$label, $value])
      <div><dt>{{ $label }}</dt><dd class="mono">{{ $value }}</dd></div>
    @endforeach
  </dl>
  @if (\App\Services\Escrow::notes() !== '')<pre class="wire">{{ \App\Services\Escrow::notes() }}</pre>@endif
  <p class="hint">Before sending your first wire, confirm these details with us at {{ brand('support_email') }}. We will never ask you to send money anywhere else.</p>
</div>
