@extends('billing::layouts.master')

@section('content')
    <h1>{{ __('Hello World') }}</h1>

    <p>{{ __('Module:') }} {!! config('billing.name') !!}</p>
@endsection
