@extends('layout.page-with-navigation-and-header')

@section('title', 'Help')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Help Viewer' ]
    ]) }}
@endsection

@section('content')
    <help-viewer
        starting-page="{{ $startingPage }}"
        root-url="{{ route('multiplayer.help') }}"
    ></help-viewer>
@endsection


