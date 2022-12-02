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
        You need to select an active character in order to see this content.
        Use the character selection in the top bar to set a character.
    </div>
@endsection


