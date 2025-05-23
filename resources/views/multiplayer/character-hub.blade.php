@extends('layout.page-with-navigation-and-header')

@section('title', 'Character')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Character' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Character</h1>
        </div>

        <div class="row">
            <p class="lead">This page acts as a hub for all the things you can view or edit on your character.</p>
        </div>

        <div class="row">
            <h2>Edit Core Details</h2>
            <character-edit></character-edit>
        </div>

        <div class="row">
            <h2>Preferences</h2>
            <p>There are a lot of preferences not presently accounted for on the webpage.</p>
            <p>And enough in editplayer to potentially make this a separate page by itself.</p>
            <p>TODO: Figure out all the preferences</p>
            <p>TODO: Control email preferences for this character.</p>
            <p>TODO: Training tax.</p>
        </div>
    </div>
@endsection

@section('links')
    <!-- TODO: Investigate pills to show unspent points on things like professions -->
    {{ PageLinks::render([
        ['title' => 'Perks', 'description' => 'Buy new perks that give (mostly) positive benefits.', 'url' => route('multiplayer.perks')],
        ['title' => 'Quirks', 'description' => 'Buy different things that are essentially a different type of perk?', 'url' => route('multiplayer.quirks')],
        ['title' => 'Classes', 'description' => 'Change your class. What does this do for you?', 'url' => route('multiplayer.classes')],
        ['title' => 'Professions', 'description' => 'Pick professions to get free initial proficiencies.', 'url' => route('multiplayer.professions')],
        ['title' => 'Training', 'description' => 'Purchase upgrades to mutant powers, proficiencies or resources.', 'url' => route('multiplayer.training')],
        ['title' => 'Dedications', 'description' => 'Get information about dedications or switch your current one.', 'url' => route('multiplayer.dedications')],
        ['title' => 'Avatar', 'description' => 'Customize the graphical representation of your character.', 'url' => route('multiplayer.avatar.edit')],
        ['title' => 'Kinks', 'description' => 'Set your detailed preferences for interacting with other characters.', 'url' => route('multiplayer.kinks')],
        ['title' => 'AI', 'description' => 'Adjust how your character acts in combat while under automatic control.', 'url' => route('multiplayer.ai')]
    ]) }}
@endsection
