@extends('layout.page-with-navigation')

@section('title', 'Settings')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Settings' ]
    ]) }}
@endsection

@section('content')
    <settings
        api-url="{{ route('settings.api') }}"
    ></settings>
@endsection
