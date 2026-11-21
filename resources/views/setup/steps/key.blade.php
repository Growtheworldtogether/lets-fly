<h1>Confirm you own this site</h1>
@if ($local)
    <p class="lead">You are opening this from the server itself, so no key is needed. From any other computer the key is required.</p>
@else
    <p class="lead">Anyone can open this page until setup is finished. To prove it is you, open this file on the server and copy the 8-character code inside it.</p>
    <code class="keybox">{{ $keyFile }}</code>
    <label>Owner key<input name="key" value="{{ old('key') }}" required maxlength="8" autocapitalize="characters" autofocus>@error('key')<span class="err">{{ $message }}</span>@enderror</label>
@endif
<div class="actions"><button class="btn">Continue</button></div>
