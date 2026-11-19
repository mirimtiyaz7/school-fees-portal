<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') | School Fees Portal</title><link rel="stylesheet" href="{{ asset('portal.css') }}"></head>
<body><header><div class="bar"><strong>School Fees Portal</strong>@auth<form method="post" action="{{ url('/logout') }}">@csrf<button class="quiet">Sign out</button></form>@endauth</div></header>
<main>@if($errors->any())<p class="alert" role="alert">{{ $errors->first() }}</p>@endif
@if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
@yield('content')</main></body></html>
