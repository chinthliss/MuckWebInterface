@extends('layout.page-with-navigation')

@section('title', "$character->name - Profile")

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => $character->name ]
    ]) }}
@endsection

@section('content')
    <character-profile
        :character-in="{{ json_encode($character->ToPlayerArray()) }}"
        :controls="{{ $controls }}"
        avatar-url="{{ $avatarUrl }}"
        :avatar-width="{{ $avatarWidth / 2 }}"
        :avatar-height="{{ $avatarHeight / 2 }}"
    ></character-profile>
@endsection


