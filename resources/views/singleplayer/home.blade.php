@extends('layout.page-with-navigation')

@section('title', 'Singleplayer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Singleplayer' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        TODO: Singleplayer home content

        Most likely a link to the blog but consider moving or having a copy of 'how to download/play' here.
    </div>
@endsection


