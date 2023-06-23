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
    <avatar-edit
        :avatar-width="{{ $avatarWidth }}"
        :avatar-height="{{ $avatarHeight }}"
        :items-in="{{ json_encode($items) }}"
        :backgrounds-in="{{ json_encode($backgrounds) }}"
        :gradients-in="{{ json_encode($gradients) }}"
        render-url="{{ route('multiplayer.avatar.edit.render') }}"
        state-url="{{ route('multiplayer.avatar.state') }}"
        gradient-url="{{ route('multiplayer.avatar.gradient.buy') }}"
        item-url="{{ route('multiplayer.avatar.item.buy') }}"
    >
    </avatar-edit>
@endsection

@section('links')
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
@endsection
