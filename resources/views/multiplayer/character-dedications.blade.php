@extends('layout.page-with-navigation-and-header')

@section('title', 'Dedications')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.character', 'label' => 'Character' ],
        [ 'label' => 'Dedications' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Dedications</h1>
        </div>

        <p class="lead">Dedications are ..</p>
        <p>TODO: What are dedications?</p>
        <character-dedications></character-dedications>
    </div>
@endsection
