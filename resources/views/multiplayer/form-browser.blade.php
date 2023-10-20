@extends('layout.page-with-navigation-and-header')

@section('title', 'Form Browser')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Form Browser' ]
    ]) }}
@endsection

@section('content')
    <form-browser
        starting-player-name="{{ $startingPlayerName }}"
        :staff="{{ $staff ? 'true' : 'false' }}"
    ></form-browser>
@endsection


