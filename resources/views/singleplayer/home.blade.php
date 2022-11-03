@extends('layout.page-singleplayer')

@section('title', 'Singleplayer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Singleplayer' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        Test body content
    </div>
@endsection


