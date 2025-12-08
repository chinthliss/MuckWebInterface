@extends('layout.page-with-navigation-and-header')

@section('title', 'Account')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Account' ]
    ]) }}
@endsection

@section('content')
    <account
        :account-in="{{ json_encode($account) }}"
        :links="{{ json_encode([
            "changeEmail" => route('auth.email.change'),
            "newEmail" => route('auth.email.new')
        ]) }}"
    ></account>
@endsection

@section('links')
    {{ PageLinks::render([
        [
            'title' => 'Change Account Password',
            'description' => 'Change the password used for this webpage and to control your account.',
            'url' => route('auth.password.change')
        ],
        [
            'title' => 'Change Character Password',
            'description' => 'Change the password used to access an individual character on the game.',
            'url' => route('multiplayer.character.changepassword')
        ],
        [
            'title' => 'Delete Account',
            'description' => 'Start or finalize the deletion of your account.',
            'url' => route('account.delete')
        ],
        [
            'title' => 'Card Management',
            'description' => 'View, add and remove cards associated with your account.',
            'url' => route('payment.cardmanagement')
        ],
        [
            'title' => 'Transactions',
             'description' => 'View any transactions made with money.',
             'url' => route('multiplayer.professions')
        ],
        [
            'title' => Lex::get('accountcurrency') . ' History',
            'description' => 'View any changes to your ' . Lex::get('accountcurrency') . '.',
            'url' => route('account.history')
        ]
    ]) }}
@endsection

