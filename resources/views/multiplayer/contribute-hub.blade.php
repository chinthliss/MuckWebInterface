@extends('layout.page-with-navigation-and-header')

@section('title', 'Contribute')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Contribute' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Contribute</h1>
        </div>

        <div class="row">
            <p class="lead">This page acts as a hub for all the ways you can contribute to the game.</p>
        </div>

    </div>
@endsection

@section('links')
    <!-- TODO: Investigate and scaffold out the contribute pages properly -->
    {{ PageLinks::render([
        ['title' => 'StringParsing Scratchpad', 'description' => 'Just a scratchpad to tinker with StringParsing', 'url' => route('multiplayer.contribute.stringparsingscratchpad')],
        ['title' => 'Forms', 'description' => 'Editor for monsters / forms / mutations', 'url' => route('multiplayer.contribute.forms')],
    ]) }}
@endsection
