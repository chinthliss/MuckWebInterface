@extends('layout.page-with-navigation-and-header')

@section('title', 'Gear')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Gear' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Gear</h1>
        </div>

        <div class="row">
            <p class="lead">This page acts as a hub for all things get related.</p>
        </div>

    </div>
@endsection

@section('links')
    <!-- TODO: Investigate and scaffold out the gear pages properly -->
    {{ PageLinks::render([
        ['title' => 'Inventory', 'description' => 'Not implemented yet', 'url' => route('multiplayer.gear.inventory')],
        ['title' => 'Crafting', 'description' => 'Creating new gear, or request such from someone else,', 'url' => route('multiplayer.gear.crafting')],
    ]) }}
@endsection
