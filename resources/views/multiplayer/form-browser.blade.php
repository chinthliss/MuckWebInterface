@extends('layout.page-with-navigation')

@section('title', 'Help')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Form Browser' ]
    ]) }}
@endsection

@section('content')
    <form-browser
    ></form-browser>
@endsection


