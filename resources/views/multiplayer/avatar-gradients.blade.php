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
    <avatar-gradient-creator
        base-preview-url="{{ route('avatar.gradient.preview') }}"
    ></avatar-gradient-creator>
@endsection


