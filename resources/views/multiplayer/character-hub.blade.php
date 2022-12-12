@extends('layout.page-multiplayer')

@section('title', 'Edit Character')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Edit Character' ]
    ]) }}
@endsection

<!-- TODO: Profile and Inventory were initially listed here and need relocating -->
@php ($pages = [
        ['title' => 'Avatar', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Settings', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Inventory', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Training', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Perks', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Quirks', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Skills', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Kinks', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Classes', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'Dedication', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
        ['title' => 'AI', 'description' => 'What does this do?', 'url' => route('multiplayer.character')],
])

@section('content')
    <div class="container">
        <div class="row">
            <h1>Edit Character</h1>
        </div>
        <div class="row">
            <p>This page acts as a hub for all the things you can edit on your character.</p>
            <p>Base character settings can maybe go here, e.g. short description</p>
        </div>
        <div class="row">
            <div class="container">
                <ul>
                    @foreach ($pages as $page)
                        <li>{{ $page['title'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection


