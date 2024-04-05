@extends('layout.page-with-navigation-and-header')

@section('title', 'Form Editor')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.contribute', 'label' => 'Contribute' ],
        [ 'label' => 'Form Editor' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Form Editor</h1>
        </div>

        <div class="lead">Interchangeably known as monsters, infections or mutations.</div>

        <form-editor></form-editor>
    </div>
@endsection
