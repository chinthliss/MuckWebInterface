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

        <p>You can also use this page to set notes upon perks you already own.</p>

        <character-perks

        ></character-perks>
    </div>
@endsection
