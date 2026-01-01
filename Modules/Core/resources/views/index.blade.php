@extends('core::layouts.master')

@section('content')
    <h1>{{ __('Hello World') }}</h1>

    <p>{{ __('Module:') }} {!! config('core.name') !!}</p>
@endsection
