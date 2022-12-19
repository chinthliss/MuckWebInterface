@extends('layout.page-multiplayer')

@section('title', 'Pending Multiplayer Content')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Pending' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        This content is still pending being written.
    </div>
@endsection


