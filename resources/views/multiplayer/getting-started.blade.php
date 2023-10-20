@extends('layout.page-with-navigation-and-header')

@section('title', 'Getting Started')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Getting Started' ]
    ]) }}
@endsection

@section('content')
    <multiplayer-getting-started
        :account="{{ $hasAccount ? 'true' : 'false' }}"
        account-url="{{ route('multiplayer.home') }}"

        :character="{{ $hasAnyCharacter ? 'true' : 'false' }}"
        character-url="{{ route('multiplayer.character.create') }}"

        :character-active="{{ $hasActiveCharacter ? 'true' : 'false' }}"

        :character-approved="{{ $hasApprovedCharacter ? 'true' : 'false' }}"
        character-approved-url="{{ route('multiplayer.character.initial-setup') }}"

        direct-connect-url="{{ route('multiplayer.home') }}"
        reset-character-password-url="{{ route('multiplayer.character.changepassword') }}"

        :page-recommendations="{{ json_encode($pageRecommendations) }}"
    ></multiplayer-getting-started>
@endsection


