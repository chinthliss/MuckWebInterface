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
<!-- TODO: Add form list page (presently on the bottom of the existing skills page.) -->
@php ($pages = [
        ['title' => 'Perks', 'description' => 'Buy new perks that give (mostly) positive benefits.', 'url' => route('multiplayer.perks')],
        ['title' => 'Quirks', 'description' => 'Buy different things that are essentially a different type of perk?', 'url' => route('multiplayer.quirks')],
        ['title' => 'Perk Notes', 'description' => 'Add optional information to your perks.', 'url' => route('multiplayer.perknotes')],
        ['title' => 'Classes', 'description' => 'Change your class. What does this do for you?', 'url' => route('multiplayer.classes')],
        ['title' => 'Professions', 'description' => 'Pick professions to get free initial proficiencies. (TODO: Investigate pill to show unspent)', 'url' => route('multiplayer.professions')],
        ['title' => 'Training', 'description' => 'Purchase upgrades to mutant powers, proficiencies or resources.', 'url' => route('multiplayer.training')],
        ['title' => 'Dedication', 'description' => 'Get information about dedications or switch your current one. There\'s also some fake terminal output?', 'url' => route('multiplayer.dedication')],
        ['title' => 'Avatar', 'description' => 'Customize the graphical representation of your character.', 'url' => route('multiplayer.avatar.edit')],
        ['title' => 'Kinks', 'description' => 'Set your detailed preferences for interacting with other characters.', 'url' => route('multiplayer.kinks')],
        ['title' => 'AI', 'description' => 'Adjust how your character acts in combat while under automatic control.', 'url' => route('multiplayer.ai')],
])

@section('content')
    <div class="container">
        <div class="row">
            <h1>Edit Character</h1>
        </div>
        <div class="row">
            <p>This page acts as a hub for all the things you can edit on your character.</p>
            <p>TODO: Description and short description</p>
        </div>

        <div class="row">
            <h2>Preferences</h2>
            <p>TODO: Control email preferences for this character.</p>
            <p>TODO: Training tax.</p>
        </div>

        <div class="row">
            <h2>Links</h2>
        </div>
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

