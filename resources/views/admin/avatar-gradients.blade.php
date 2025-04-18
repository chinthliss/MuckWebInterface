@extends('layout.page-with-navigation-and-header')

@section('title', 'Avatar Gradients (Admin)' )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Avatar Gradients (Admin)' ]
    ]) }}
@endsection

@section('content')
    <avatar-gradient-viewer
        :admin-mode="{{ 'true' }}"
        :gradients="{{ json_encode($gradients) }}"
    >
    </avatar-gradient-viewer>
@endsection


