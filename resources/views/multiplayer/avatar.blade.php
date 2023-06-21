@extends('layout.page-with-navigation')

@section('title', 'Edit Avatar')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.character', 'label' => 'Edit Character' ],
        [ 'label' => 'Edit Avatar' ]
    ]) }}
@endsection

@php ($pages = [
    ['title' => 'Avatar Gradients', 'description' => 'Preview all the gradients or create a new one.', 'url' => route('multiplayer.avatar.gradients')],
])

@section('content')
    Pending

    <!-- TODO: Unify links on hub screens -->
    <div class="container">
        <h2>Links</h2>
        <div class="row g-2">
            @foreach ($pages as $page)
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $page['title'] }}</h5>
                            <div class="card-text">{{ $page['description'] }}</div>
                            <a href="{{ $page['url'] }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@endsection


