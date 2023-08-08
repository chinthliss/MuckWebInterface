@extends('layout.page-with-navigation')

@section('title', 'Site Log Viewer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Site Log Viewer' ]
    ]) }}
@endsection

@section('content')
    <admin-log-viewer
        :dates="{{ json_encode($dates) }}"
    ></admin-log-viewer>
@endsection
