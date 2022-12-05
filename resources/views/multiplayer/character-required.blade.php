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
        You need to have an active character in order to see this content.
        Use the character selection in the top bar to select an existing character or to create a new one.
    </div>
@endsection


