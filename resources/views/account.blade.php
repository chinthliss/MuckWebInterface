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
            "changePassword" => route('auth.password.change'),
            "changeEmail" => route('auth.email.change'),
            "newEmail" => route('auth.email.new'),
            "cardManagement" => route('payment.cardmanagement'),
            "accountCurrencyHistory" => route('account.history'),
            "transactions" => route('account.transactions'),
        ]) }}"
    ></account>
@endsection
