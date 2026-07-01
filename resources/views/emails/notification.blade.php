@extends('emails.layout')

@section('title', $subject ?? config('app.name'))

@section('content')
    {!! $content !!}
@endsection

@section('footer')
    This is an automated email from {{ $appName ?? config('app.name') }}
@endsection