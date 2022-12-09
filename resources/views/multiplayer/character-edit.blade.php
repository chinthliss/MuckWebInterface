@extends('layout.page-multiplayer')

@section('title', 'Edit Character')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Edit Character' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Edit Character</h1>
        </div>
        <div class="row">
            <p>This page acts as a hub for all the things you can edit on your character.</p>
        </div>
        <div class="row">
            <div class="container">
                <ul>
                    <li>Avatar</li>
                    <li>Profile (Should not be here, since it's a viewer?)</li>
                    <li>Settings</li>
                    <li>Inventory</li>
                    <li>Skills</li>
                    <li>Training</li>
                    <li>Perks</li>
                    <li>Quirks</li>
                    <li>Classes</li>
                    <li>Dedications</li>
                    <li>Ai</li>
                </ul>
            </div>
        </div>
    </div>
@endsection


