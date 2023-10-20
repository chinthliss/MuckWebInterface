@extends('layout.page-with-navigation-and-header')

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
        avatar-url="{{ $avatarUrl }}"
    ></character-profile>
@endsection


