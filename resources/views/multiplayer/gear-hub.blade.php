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
        <h1>Gear</h1>

        <div class="row">
            <p class="lead">This page acts as a hub for all things inventory related.
                This includes salvage, currencies, etc.</p>
        </div>

        <h2>Equipped Items</h2>
        <p>Not implemented yet, this is just to scaffold out the page.</p>
        <!-- TODO: Inventory component -->

        <h2>Inventory</h2>
        <p>Not implemented yet, this is just to scaffold out the page.</p>
        <!-- TODO: Inventory component -->

        <h2>Salvage</h2>
        <gear-salvage-display></gear-salvage-display>

        <h2>Nanites</h2>
        <p>Not implemented yet, this is just to scaffold out the page.</p>
        <!-- TODO: Nanite component -->

        <h2>Other</h2>
        <p>Not implemented yet, this is just to scaffold out the page.</p>
        <!-- TODO: Other inventory component -->

    </div>
@endsection

@section('links')
    <!-- TODO: Investigate and scaffold out the gear pages properly -->
    {{ PageLinks::render([
        ['title' => 'Crafting', 'description' => 'Creating new gear, or request such from someone else,', 'url' => route('multiplayer.gear.crafting')],
    ]) }}
@endsection
