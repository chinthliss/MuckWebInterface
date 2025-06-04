@extends('layout.page-with-navigation-and-header')

@section('title', 'Information')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Information' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Information</h1>
        </div>

        <div class="row">
            <p class="lead">These pages will provide, not surprisingly, more information on things.</p>
            <!-- TODO: Write a better opening for Information page -->
        </div>

    </div>
@endsection

@section('links')
    <!-- TODO: Investigate and scaffold out the information pages properly -->
    {{ PageLinks::render([
        ['title' => 'Form Browser', 'description' => 'View the forms available in game', 'url' => route('multiplayer.info.forms')],
        ['title' => 'Status Browser', 'description' => 'View the statuses used by the game', 'url' => route('multiplayer.info.statuses')]
    ]) }}
@endsection
