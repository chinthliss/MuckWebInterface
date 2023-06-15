@extends('layout.page-with-navigation')

@section('title', 'Avatar Items (Admin)' )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Avatar Items (Admin)' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-item-viewer
        :items="{{ json_encode($items) }}"
        :file-usage="{{ json_encode($fileUsage) }}"
    >
    </admin-avatar-item-viewer>
@endsection


