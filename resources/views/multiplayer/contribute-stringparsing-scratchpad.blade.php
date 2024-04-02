@extends('layout.page-with-navigation-and-header')

@section('title', 'StringParsing Scratchpad')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.contribute', 'label' => 'Contribute' ],
        [ 'label' => 'StringParsing Scratchpad' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>StringParsing Scratchpad</h1>
        </div>

        <div class="lead">The editor below isn't linked to anything and is just to test parsing.</div>

        <editor-string-parsing></editor-string-parsing>

    </div>
@endsection
