@extends('layout.page-multiplayer')

@section('title', 'Select Character')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Select Character' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Character Required</h1>
        </div>
        <div class="row">
            <p>You need to have an active character in order to see this content.</p>
            <p>Use the character selection in the top bar to select an existing character or to create a new one.</p>
        </div>
    </div>
@endsection


