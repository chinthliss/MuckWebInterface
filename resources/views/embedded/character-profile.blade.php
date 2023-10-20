@extends('layout.page-without-navigation-or-header')

@section('title', "$character->name - Profile")

@section('content')
    <character-profile
        :character-in="{{ json_encode($character->ToPlayerArray()) }}"
        avatar-url="{{ $avatarUrl }}"
    ></character-profile>
@endsection


