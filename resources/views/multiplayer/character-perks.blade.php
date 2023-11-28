@extends('layout.page-with-navigation-and-header')

@section('title', 'Perks')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Perks' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Perks</h1>
        </div>

        <p>You can use this page to browse perks available, whether just to investigate them or to purchase a new one.</p>

        <p>For perks you own, you can also add/edit custom notes on them. These are used to detail anything you'd like,
            such as how your character uses the perk, how they obtained it or any other limitations. These may be
            referred to by scene-runners.</p>

        <!-- TODO: Investigate removal of perks, including free removals during first week -->

        <character-perks

        ></character-perks>
    </div>
@endsection
