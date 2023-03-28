@extends('layout.page-with-navigation')

@section('title', 'Notifications')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Notifications' ]
    ]) }}
@endsection

@section('content')
    <notifications
        api-url="{{ route('notifications.api') }}"
    ></notifications>
@endsection


