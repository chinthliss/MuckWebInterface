@extends('layout.page-with-navigation-and-header')

@section('title', 'Dedications')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.character', 'label' => 'Character' ],
        [ 'label' => 'Dedications' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Dedications</h1>
        </div>

        <p class="lead">
            Each Dedication has a certain bit of theme behind it, be it supernatural, scientific, or just pure training.
            Devoting to one can grant unique boons, powers and abilities.
        </p>
        <p>
            For more information see the help file
            <a href="{{ route('multiplayer.help') }}/Theme/dedications">+help Theme/dedications</a>.
        </p>
        <character-dedications></character-dedications>
    </div>
@endsection
