@extends('layout.page-with-navigation')

@section('title', 'Setup a New Character')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Setup a New Character' ]
    ]) }}
@endsection

@section('content')
    PENDING
@endsection


