@extends('layout.page-with-navigation-and-header')

@section('title', "Salvage Market")

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.gear', 'label' => 'Gear' ],
        [ 'label' => 'Salvage Market' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <h1>Salvage Market</h1>

        <div class="lead">
            Salvage Market. Which needs a better opening and is a work in progress.
        </div>
        <!-- TODO Write better intro to salvage market -->

        <callout class="my-2" type="warning">
            This page is still under initial development and unfinished. Missing:
            <br/>Final purchase is unimplemented
            <br/>Disabling buttons if player can't perform the action at all
            <br/>Showing current owned quantity in the transaction dialog and dynamically changing max transactions
        </callout>


        <div class="mt-2">
            <gear-salvage-market></gear-salvage-market>
        </div>

        <h2>Auto-Purchase configuration</h2>

        <p>In some cases, the game can auto-purchase salvage for you.
            Here is where you can set your limits on those purchases.
        </p>
        <gear-salvage-auto-purchase-config></gear-salvage-auto-purchase-config>
    </div>
@endsection


