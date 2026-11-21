<h1>Site address</h1>
<p class="lead">The public address of the site. Links and the logo in emails use it, so enter the real address (https://...) if the site is going live.</p>
<div class="grid">
    <label class="wide">Web address<input name="url" value="{{ old('url', $d['url'] ?? $suggestedUrl) }}" required>@error('url')<span class="err">{{ $message }}</span>@enderror</label>
    <label class="wide">Time zone<select name="tz">@foreach ($tzs as $id => $name)<option value="{{ $id }}" @selected(old('tz', $d['tz'] ?? 'America/New_York') === $id)>{{ $name }} ({{ $id }})</option>@endforeach</select><small>Dates, daily jobs and the wire cutoff use it. Set it now: changing it later shifts the times already saved.</small>@error('tz')<span class="err">{{ $message }}</span>@enderror</label>
</div>
<div class="actions"><button class="btn">Continue</button></div>
