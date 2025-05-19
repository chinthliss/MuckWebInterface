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

        <div class="row">
            <h1>Crafting</h1>
        </div>

        <div class="lead">
            Crafting page. Which needs a better opening.
        </div>
        <!-- TODO Write better intro to crafting page -->

        <gear-crafting></gear-crafting>
    </div>
@endsection


