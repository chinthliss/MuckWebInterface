@extends('layout.page-with-navigation')

@section('title', 'Edit Avatar')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.character', 'label' => 'Edit Character' ],
        [ 'label' => 'Edit Avatar' ]
    ]) }}
@endsection

@section('content')
    <avatar-edit
        :avatar-width-in="{{ $avatarWidth }}"
        :avatar-height-in="{{ $avatarHeight }}"
        :items-in="{{ json_encode($items) }}"
        :backgrounds-in="{{ json_encode($backgrounds) }}"
        :gradients-in="{{ json_encode($gradients) }}"
        render-url="{{ route('multiplayer.avatar.edit.render') }}"
        state-url="{{ route('multiplayer.avatar.state') }}"
        gradient-url="{{ route('multiplayer.avatar.gradient.buy') }}"
        item-url="{{ route('multiplayer.avatar.item.buy') }}"
    >
    </avatar-edit>
@endsection

@section('links')
    {{ PageLinks::render([
        ['title' => 'Avatar Gradients', 'description' => 'Preview all the gradients or create a new one.', 'url' => route('multiplayer.avatar.gradients')]
    ]) }}
@endsection
