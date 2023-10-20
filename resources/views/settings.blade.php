@extends('layout.page-with-navigation-and-header')

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
        :initial-settings="{{ json_encode($settings) }}"
    ></settings>
@endsection
