@extends('layout.page-with-navigation-and-header')

@section('title', 'Statuses')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.info', 'label' => 'Information' ],
        [ 'label' => 'Status Browser' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Status Browser</h1>
        </div>

        <div class="lead">
            Statuses. 100% chance for this page not to have a better opening paragraph, for up to 1,000,000.0 rounds.
        </div>
        <!-- TODO Write better intro to status browser page -->

        <status-browser class="mt-2"></status-browser>
    </div>
@endsection


