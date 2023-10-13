@extends('layout.page-with-navigation')

@section('title', 'Pending Multiplayer Content')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Pending Content' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <p class="lead">This content hasn't been written yet.</p>
        <p>This is just a placeholder to help layout the skeleton for the site.</p>
    </div>
@endsection


