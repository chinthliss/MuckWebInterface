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
<!-- TODO: Look at bringing across the things under the 'settings' page onto this page -->
@php ($pages = [
        ['title' => 'Avatar', 'description' => 'Customize the graphical representation of your character.', 'url' => route('multiplayer.character')],
        ['title' => 'Perks', 'description' => 'Buy new perks that give (mostly) positive benefits.', 'url' => route('multiplayer.character')],
        ['title' => 'Perk Notes', 'description' => 'Add optional information to your perks.', 'url' => route('multiplayer.character')],
        ['title' => 'Quirks', 'description' => 'Buy different things that are essentially a different type of perk?', 'url' => route('multiplayer.character')],
        ['title' => 'Classes', 'description' => 'Change your class.', 'url' => route('multiplayer.character')],
        ['title' => 'Skills', 'description' => 'Select your professions. And shows some things that should be on the profile page.', 'url' => route('multiplayer.character')],
        ['title' => 'Training', 'description' => 'Something about proficiencies? And upgrades some things.', 'url' => route('multiplayer.character')],
        ['title' => 'Kinks', 'description' => 'Set your detailed preferences for interacting with other characters.', 'url' => route('multiplayer.character')],
        ['title' => 'Dedication', 'description' => 'Get information about dedications or switch your current one. There\'s also some fake terminal output?', 'url' => route('multiplayer.character')],
        ['title' => 'AI', 'description' => 'Adjust how your character acts in combat whilst under automatic control.', 'url' => route('multiplayer.character')],
])

@section('content')
    <div class="container">
        <div class="row">
            <h1>Edit Character</h1>
        </div>
        <div class="row">
            <p>This page acts as a hub for all the things you can edit on your character.</p>
            <p>TODO: Look at bringing across the other toggles on the settings page onto this page.</p>
            <p>Base character settings go here, e.g. short description</p>
        </div>
        <div class="row g-2">
            @foreach ($pages as $page)
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $page['title'] }}</h5>
                            <div class="card-text">{{ $page['description'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection


