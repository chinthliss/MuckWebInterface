@extends('layout.page-with-navigation')

@section('title', 'Getting Started')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Getting Started' ]
    ]) }}
@endsection

@section('content')
    <!-- TODO: Connect missing urls up in getting started -->
    <multiplayer-getting-started
        :account="{{ $hasAccount ? 'true' : 'false' }}"
        account-url="{{ route('multiplayer.home') }}"

        :character="{{ $hasAnyCharacter ? 'true' : 'false' }}"
        character-url="{{ route('multiplayer.home') }}"

        :character-active="{{ $hasActiveCharacter ? 'true' : 'false' }}"

        :character-approved="{{ $hasApprovedCharacter ? 'true' : 'false' }}"
        character-approved-url="{{ route('multiplayer.home') }}"

        direct-connect-url="{{ route('multiplayer.home') }}"
        reset-character-password-url="{{ route('multiplayer.character.changepassword') }}"

        :page-recommendations="{{ json_encode($pageRecommendations) }}"
    ></multiplayer-getting-started>
@endsection


