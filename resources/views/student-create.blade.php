@extends('layout')
@section('title', 'Add student')
@section('content')
<section class="card login">
<a href="{{ route('home') }}">Back to fee collections</a>
<h1>Add student</h1>
<form method="post" action="{{ route('students.store') }}">@csrf
<label for="student">Student name</label>
<input id="student" name="student" maxlength="120" value="{{ old('student') }}" required>
<label for="parent_id">Parent account</label>
<select id="parent_id" name="parent_id" required>
<option value="">Choose a parent</option>
@foreach($parents as $parent)
<option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->name }} ({{ $parent->email }})</option>
@endforeach
</select>
<label for="fee">Opening fee (₹)</label>
<input type="number" name="fee" id="fee" min="0.01" max="99999999.99" step="0.01" value="{{ old('fee') }}" required>
<button>Add student</button>
</form></section>
@endsection
