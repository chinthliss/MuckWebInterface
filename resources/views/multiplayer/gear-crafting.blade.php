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

        <callout>
            <p>This page is extremely under-construction. Even more so than the rest of the site!</p>
            <div>Missing content:</div>
            <ul>
                <li>The actual crafting! At the moment this page is just testing the preview functionality</li>
                <li>Most of the constructive warning messages (e.g. 'this could be crafted if ..')</li>
                <li>Saved Plan functionality</li>
                <li>History functionality</li>
                <li>Commissioning functionality</li>

            </ul>
        </callout>

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


