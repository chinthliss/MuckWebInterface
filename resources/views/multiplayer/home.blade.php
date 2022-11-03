@extends('layout.page-multiplayer')

@section('title', 'Multiplayer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Multiplayer' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        Test body content
    </div>
@endsection


