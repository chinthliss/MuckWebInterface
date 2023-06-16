@extends('layout.page-with-navigation')

@section('title', 'Avatar Gradients' )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar Gradients' ]
    ]) }}
@endsection

@section('content')
    <avatar-gradient-viewer
        :gradients="{{ json_encode($gradients) }}"
    >
    </avatar-gradient-viewer>
@endsection


