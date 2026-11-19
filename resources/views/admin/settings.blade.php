@extends('layouts.app')
@section('mainclass', 'wide')
@section('content')
<div class="page">@include('admin._nav')
  <h1>Settings</h1>
  <form method="post" action="{{ route('admin.settings.save') }}" class="stack">@csrf
    <h2>Names</h2>
    <label for="company_name">Company name</label><input id="company_name" name="company_name" class="field" maxlength="80" value="{{ old('company_name', $s['company_name']) }}" placeholder="[Company Name]">
    <label for="vehicle_name">Vehicle name</label><input id="vehicle_name" name="vehicle_name" class="field" maxlength="80" value="{{ old('vehicle_name', $s['vehicle_name']) }}" placeholder="[Vehicle Name]">
    <label for="sender_name">Email sender name</label><input id="sender_name" name="sender_name" class="field" maxlength="80" value="{{ old('sender_name', $s['sender_name']) }}">
    <label for="support_email">Support email</label><input id="support_email" name="support_email" type="email" class="field" value="{{ old('support_email', $s['support_email']) }}">
    <label for="footer_text">Footer line</label><textarea id="footer_text" name="footer_text" class="field area" maxlength="500">{{ old('footer_text', $s['footer_text']) }}</textarea>
    <p class="hint">Leave empty for the standard line. [Company Name] is replaced with the company name.</p>

    <h2>Member numbers</h2>
    <label for="member_prefix">Prefix letters</label><input id="member_prefix" name="member_prefix" class="field" maxlength="6" required value="{{ old('member_prefix', $s['member_prefix'] ?: config('pioneer.member_prefix')) }}">
    <label for="member_digits">Digits</label><input id="member_digits" name="member_digits" type="number" min="3" max="10" class="field" required value="{{ old('member_digits', $digits) }}">
    <label for="member_next">Next number</label><input id="member_next" name="member_next" type="number" min="1" class="field" required value="{{ old('member_next', $next) }}">
    <p class="hint">Changing these affects new members only.</p>

    <h2>Pricing (USD)</h2>
    <label for="price">Pioneer price</label><input id="price" name="price" type="number" step="0.01" class="field" required value="{{ old('price', $price) }}">
    <label for="public_price">Projected public price</label><input id="public_price" name="public_price" type="number" step="0.01" class="field" required value="{{ old('public_price', $public_price) }}">
    <label for="deposit">Deposit</label><input id="deposit" name="deposit" type="number" step="0.01" class="field" required value="{{ old('deposit', $deposit) }}">
    <label for="balance_days">Days to pay the balance after the deposit is verified</label><input id="balance_days" name="balance_days" type="number" class="field" required value="{{ old('balance_days', $days) }}">

    <h2>Wire details</h2>
    <label for="wire_details">Shown on invoices</label><textarea id="wire_details" name="wire_details" class="field area" maxlength="2000">{{ old('wire_details', $wire) }}</textarea>

    <h2>Sign-in</h2>
    <label class="check"><input type="checkbox" name="country_check" value="1" @checked(old('country_check', $country))> Email a code when a member signs in from a different country</label>

    <button class="btn solid" type="submit">Save settings</button>
  </form>
</div>
@endsection
