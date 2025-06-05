@extends('layout.page-with-navigation-and-header')

@section('title', "Crafting")

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.gear', 'label' => 'Gear' ],
        [ 'label' => 'Crafting' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <div class="mt-2 mb-4 p-3 border rounded border-warning text-warning">
            This page is extremely under-construction. Even more so than the rest of the site!
        </div>

        <h1>Crafting</h1>

        <div class="lead">
            Crafting page. Which needs a better opening.
        </div>
        <!-- TODO Write better intro to crafting page -->

        <div class="mt-2">
            <gear-crafting></gear-crafting>
        </div>
    </div>
@endsection


