@extends('layout')
@section('title', 'Sign in')
@section('content')
<section class="card login"><p class="eyebrow">School accounts</p><h1>Sign in</h1><p>View your fee balance and payment receipts.</p>
<form method="post" action="{{ url('/login') }}">@csrf
<label for="email">Email address</label><input name="email" id="email" type="email" autocomplete="username" value="{{ old('email') }}" required>
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<button>Sign in</button></form></section>
@endsection
