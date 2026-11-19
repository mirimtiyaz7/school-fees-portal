@extends('layout')
@section('title', $school)
@section('content')
<p class="eyebrow">{{ $school }} · {{ ucfirst($user->role) }}</p>
<h1>{{ $user->role === 'accountant' ? 'Fee collections' : 'Family fee statement' }}</h1>
<form method="get" action="{{ route('home') }}" class="search">
<label for="student-search">Search students by name</label>
<div class="entry"><input type="search" id="student-search" name="q" maxlength="120" value="{{ $search }}"><button>Search</button></div>
@if($search !== '')<a href="{{ route('home') }}">Clear search</a><p>Balances and receipts for matching students</p>@endif
</form>
@if($user->role === 'accountant')<p><a href="{{ route('students.create') }}">Add student</a></p>@endif
@php($money = fn ($paise) => '₹'.number_format($paise / 100, 2))
<section class="summary" aria-label="Summary">
<div>Total fees<strong>{{ $money($accounts->sum('fee_paise')) }}</strong></div>
<div>Collected<strong>{{ $money($accounts->sum('paid')) }}</strong></div>
<div>Outstanding<strong>{{ $money($accounts->sum('fee_paise') - $accounts->sum('paid')) }}</strong></div>
</section><h2>Student accounts</h2><div class="grid">
@forelse($accounts as $account)
<article class="card"><h3>{{ $account->student }}</h3><p>Fee {{ $money($account->fee_paise) }} · Paid {{ $money($account->paid) }}</p>
<p><b>{{ $money($account->fee_paise - $account->paid) }}</b> remaining</p>
@if($user->role === 'accountant' && $account->paid < $account->fee_paise)
<form method="post" action="{{ url('/accounts/'.$account->id.'/payments') }}">@csrf
<input type="hidden" name="request_key" value="{{ (string) Illuminate\Support\Str::uuid() }}">
<label for="amount{{ $account->id }}">Payment amount (₹)</label>
<div class="entry"><input required type="number" step="0.01" min="0.01" max="{{ number_format(($account->fee_paise - $account->paid)/100, 2, '.', '') }}" id="amount{{ $account->id }}" name="amount"><button>Record payment</button></div></form>
@endif</article>
@empty<p>{{ $search !== '' ? 'No matching students found.' : 'No student accounts are assigned yet.' }}</p>@endforelse</div>
<h2>Payment receipts</h2><div class="card">
@forelse($payments as $payment)
<div class="receipt"><div><strong>{{ $payment->student }}</strong><small>R-{{ $payment->id }} · {{ substr($payment->created_at, 0, 10) }}</small></div>
<b>{{ $money($payment->amount_paise) }}</b><a href="{{ route('receipts.print', $payment->id) }}">View / print</a>
<a href="{{ route('receipts.pdf', $payment->id) }}">Download PDF</a></div>
@empty<p>Receipts will appear here when a payment is recorded.</p>@endforelse
@if($payments->hasPages())<nav aria-label="Receipt pages">@if($payments->previousPageUrl())<a href="{{ $payments->previousPageUrl() }}">Previous</a>@endif
Page {{ $payments->currentPage() }} of {{ $payments->lastPage() }}
@if($payments->nextPageUrl())<a href="{{ $payments->nextPageUrl() }}">Next</a>@endif</nav>@endif</div>
@endsection
